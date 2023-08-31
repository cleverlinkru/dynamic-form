<?php

namespace App\Services\DynamicForm;

use App\Events\DynamicForm\DynamicFormItemChangeEvent;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Запись динамической формы
 */
class Item
{
    public ?string $id;

    public array $fields;

    public string $createdText;

    public string $userId;

    public string $userName;

    /**
     * Получение экземпляра из модели
     *
     * @param  DynamicForm  $df
     * @param $model
     * @return self
     * @throws \Exception
     */
    public static function getInstanceWithModel(DynamicForm $df, $model)
    {
        $itemModelMapper = $df->getItemModelMapper($model);

        $fields = [];
        foreach ($df->config->getFields() as $fieldConfig) {
            $field = Field::getInstanceWithModel($df, $model, $fieldConfig['name']);
            if (!$field) {
                $field = Field::getInstanceWithName($df, $fieldConfig['name']);
            }
            $fields[] = $field;
        }

        return new self([
            'df' => $df,
            'modelMapper' => $itemModelMapper,
            'fields' => $fields,
            'userId' => $itemModelMapper->get('user_id') ?? null,
        ]);
    }

    /**
     * Получение экземпляра из массива
     *
     * @param  DynamicForm  $df
     * @param  array  $item
     * @return self
     */
    public static function getInstanceWithArray(DynamicForm $df, array $item)
    {
        $itemModelMapper = $df->getItemModelMapper();

        $fields = [];
        foreach ($df->config->getFields() as $fieldConfig) {
            $field = null;
            foreach ($item['fields'] as $fieldData) {
                if ($fieldData['name'] == $fieldConfig['name']) {
                    $field = Field::getInstanceWithArray($df, $fieldData);
                    break;
                }
            }
            if (!$field) {
                $field = Field::getInstanceWithName($df, $fieldConfig['name']);
            }
            $fields[] = $field;
        }

        return new self([
            'df' => $df,
            'modelMapper' => $itemModelMapper,
            'fields' => $fields,
            'userId' => Auth::user()?->id,
        ]);
    }

    /**
     * Получение экземпляра с пустыми полями на основании только конфигкурации
     *
     * @param  DynamicForm  $df
     * @return self
     */
    public static function getInstance(DynamicForm $df)
    {
        $itemModelMapper = $df->getItemModelMapper();

        $fields = [];
        foreach ($df->config->getFields() as $fieldConfig) {
            $fields[] = Field::getInstanceWithName($df, $fieldConfig['name']);
        }

        return new self([
            'df' => $df,
            'modelMapper' => $itemModelMapper,
            'fields' => $fields,
            'userId' => Auth::user()?->id,
        ]);
    }

    /**
     * Изменение значений полей
     *
     * @param  array  $data
     * @return void
     */
    public function change(array $data)
    {
        foreach ($data['fields'] as $dataField) {
            foreach ($this->fields as $field) {
                if ($field->name == $dataField['name']) {
                    $field->change($dataField);
                }
            }
        }
    }

    /**
     * Проверка на корректность значений полей
     *
     * @return array
     */
    public function check()
    {
        $this->errors = [];

        foreach ($this->fields as $field) {
            $field->check();
            $errors = $field->getErrors();
            if ($errors) {
                $this->errors[] = [
                    'name' => $field->name,
                    'errors' => $errors,
                ];
            }
        }

        return $this->errors;
    }

    /**
     * Сохранение в базу с проверкой на корректность значений полей
     *
     * @return bool
     */
    public function save()
    {
        $errors = $this->check();

        if ($errors) {
            return false;
        }

        $changeEvent = new DynamicFormItemChangeEvent($this->df);

        if ($this->itemModelMapper->isSaved()) {
            $changeEvent->setType(Action::TYPE_EDIT);
        } else {
            $changeEvent->setType(Action::TYPE_CREATE);
            $this->itemModelMapper->set('user_id', $this->userId);
        }

        foreach ($this->fields as $field) {
            $field->setItem($this);
            $field->setItemModelMapper($this->itemModelMapper);
            $changeEvent->addFieldChange(
                $field->name,
                $field->title,
                $field->getValueText(true),
                $field->getValueText()
            );
            $field->fillItemModelMapper();
        }

        $this->itemModelMapper->save();

        $this->fillId();
        $changeEvent->setItemId($this->id);
        event($changeEvent);

        return true;
    }

    /**
     * Удаление из базы
     *
     * @return void
     */
    public function delete()
    {
        if (!$this->itemModelMapper) {
            return;
        }

        $changeEvent = new DynamicFormItemChangeEvent($this->df);
        $changeEvent->setItemId($this->id);
        $changeEvent->setType(Action::TYPE_DELETE);

        foreach ($this->fields as $field) {
            $changeEvent->addFieldChange($field->name, $field->title, $field->getValueText(), null);
        }

        $this->itemModelMapper->delete();
        $this->itemModelMapper = null;

        event($changeEvent);
    }

    /**
     * Получение ошибок, возникших после последней попытки сохранения.
     * Если ошибок не возникло, возвращается пустой массив.
     *
     * @return array|mixed
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Получение карты модели записи
     *
     * @return mixed|null
     */
    public function getItemModelMapper()
    {
        return $this->itemModelMapper;
    }

    /**
     * Получение поля по имени
     *
     * @param  string  $name
     * @return mixed|void
     */
    public function getField(string $name)
    {
        foreach ($this->fields as &$field) {
            if ($field->name == $name) {
                return $field;
            }
        }
    }


    protected DynamicForm $df;

    protected ?ItemModelMapper $itemModelMapper;

    protected array $errors = [];

    /**
     * Создание экземпляра
     *
     * @param  array  $params
     */
    protected function __construct(array $params)
    {
        $this->df = $params['df'];
        $this->itemModelMapper = $params['modelMapper'];
        $this->fields = $params['fields'];
        $this->userId = $params['userId'];

        $this->fillId();
        $this->fillCreatedText();
        $this->fillUserName();
        $this->fillFields();
    }

    /**
     * Заполнение поля ID
     *
     * @return void
     */
    protected function fillId()
    {
        $this->id = $this->itemModelMapper ? $this->itemModelMapper->get('id') : null;
    }

    /**
     * Заполнение полей
     *
     * @return void
     */
    protected function fillFields()
    {
        $fields = $this->fields;
        $this->fields = [];

        foreach ($this->df->config->getFields() as $fieldConfig) {
            $actualField = null;

            foreach ($fields as $field) {
                if ($field->name == $fieldConfig['name']) {
                    $actualField = $field;
                }
            }

            if (!$actualField) {
                $actualField = Field::getInstanceWithName($this->df, $fieldConfig['name']);
            }

            $this->fields[] = $actualField;
        }

        foreach ($this->fields as $field) {
            $field->setItem($this);
            $field->fillCanEdit();
        }
    }

    /**
     * Заполнение поля даты создания в текстовом формате
     *
     * @return void
     */
    protected function fillCreatedText()
    {
        $createdAt = $this->itemModelMapper->get('created_at');
        $this->createdText = $createdAt ? $createdAt->format('d.m.Y H:i:s') : '';
    }

    /**
     * Заполнение поля имени пользователя
     *
     * @return void
     */
    protected function fillUserName()
    {
        $this->userName = '';

        if (!$this->userId) {
            return;
        }

        $user = User::findOrFail($this->userId);
        $this->userName = $user->name;
    }
}

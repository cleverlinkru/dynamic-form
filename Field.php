<?php

namespace App\Services\DynamicForm;

use App\Services\DynamicForm\Fields\Catalog;
use App\Services\DynamicForm\Fields\DatetimeField;
use App\Services\DynamicForm\Fields\Divisions;
use App\Services\DynamicForm\Fields\Files;
use App\Services\DynamicForm\Fields\ItemCreated;
use App\Services\DynamicForm\Fields\ItemUser;
use App\Services\DynamicForm\Fields\Select;
use Exception;
use Illuminate\Database\Eloquent\Builder;

/**
 * Базовое поле динамической формы
 */
class Field
{
    public ?string $id;

    public ?string $itemId;

    public string $name;

    public string $title;

    public string $type;

    public bool $visible;

    public string $tag;

    public $value;

    public string $valueText;

    public bool $canEdit;

    public static bool $sortable = true;

    public static bool $filterable = true;

    /**
     * Получение имени класса поля на основании конфигурации
     *
     * @param  array  $configData
     * @return mixed|string
     * @throws Exception
     */
    public static function getClass(array $configData)
    {
        $type = $configData['type'];
        $typeClass = isset($configData['typeClass']) ? $configData['typeClass'] : null;
        if ($typeClass) {
            if (class_exists($typeClass)) {
                return $typeClass;
            } else {
                throw new Exception("Unknown field type class '$typeClass'");
            }
        } else {
            switch ($type) {
                case 'text':
                case 'textarea':
                case 'int':
                    return self::class;
                case 'select':
                    return Select::class;
                case 'datetime':
                    return DatetimeField::class;
                case 'files':
                    return Files::class;
                case 'divisions':
                    return Divisions::class;
                case 'catalog':
                    return Catalog::class;
                case 'itemCreated':
                    return ItemCreated::class;
                case 'itemUser':
                    return ItemUser::class;
                default:
                    throw new Exception("Unknown field type '$type'");
            }
        }
    }

    /**
     * Обработка конфигурационного массива.
     * Каждый тип поля может содержать разный набор конфигурационных полей, устанавливать свои значения по умолчанию.
     *
     * @param  array  $data
     * @return array
     */
    public static function handleConfig(array $data)
    {
        return [
            'name' => $data['name'],
            'title' => $data['title'],
            'type' => $data['type'],
            'visible' => isset($data['visible']) ? $data['visible'] : true,
            'tag' => isset($data['tag']) ? $data['tag'] : '',
            'typeClass' => isset($data['typeClass']) ? $data['typeClass'] : null,
            'validRules' => isset($data['validRules']) ? $data['validRules'] : [],
            'editRules' => isset($data['editRules']) ? $data['editRules'] : [],
        ];
    }

    /**
     * Обработка запроса фильтрации.
     * При запросе списка записей каждое поле добавляет к запросу свои условия, необходимые для фильтрации по этому полю.
     *
     * @param  Builder  $query
     * @param  array  $configField
     * @param  string  $itemModelMapperClass
     * @param $value
     * @return void
     */
    public static function handleFilter(Builder $query, array $configField, ItemModelMapper $itemModelMapper, $value)
    {
        if (!$value) {
            return;
        }

        $search = mb_strtolower($value);
        $itemModelMapper->whereLike($query, $configField['name'], $search);
    }

    /**
     * Получение экземпляра из модели
     *
     * @param  DynamicForm  $df
     * @param $model
     * @param  string  $fieldName
     * @return mixed
     * @throws Exception
     */
    public static function getInstanceWithModel(
        DynamicForm $df,
        $model,
        string $fieldName
    ) {
        $itemModelMapper = $df->getItemModelMapper($model);
        $fieldConfig = $df->config->getField($fieldName);
        $class = self::getClass($fieldConfig);
        return new $class([
            'df' => $df,
            'modelMapper' => $itemModelMapper,
            'name' => $fieldName,
            'value' => $itemModelMapper->get($fieldName),
        ]);
    }

    /**
     * Получение экземпляра из массива
     *
     * @param  DynamicForm  $df
     * @param  array  $data
     * @return mixed
     * @throws Exception
     */
    public static function getInstanceWithArray(DynamicForm $df, array $data)
    {
        $itemModelMapper = $df->getItemModelMapper();
        $fieldConfig = $df->config->getField($data['name']);
        $class = self::getClass($fieldConfig);
        return new $class([
            'df' => $df,
            'modelMapper' => $itemModelMapper,
            'name' => $data['name'],
            'value' => $data['value'],
        ]);
    }

    /**
     * Получение экземпляра по переданному имени
     *
     * @param  DynamicForm  $df
     * @param  string  $name
     * @return mixed
     * @throws Exception
     */
    public static function getInstanceWithName(DynamicForm $df, string $name)
    {
        $itemModelMapper = $df->getItemModelMapper();
        $fieldConfig = $df->config->getField($name);
        $class = self::getClass($fieldConfig);
        return new $class([
            'df' => $df,
            'modelMapper' => $itemModelMapper,
            'name' => $name,
            'value' => '',
        ]);
    }

    /**
     * Установка записи, к которой относится текущее поле
     *
     * @param  Item  $item
     * @return void
     */
    public function setItem(?Item $item)
    {
        $this->item = $item;
        $this->fillItemId();
    }

    /**
     * Изменение значения
     *
     * @param  array  $data
     * @return void
     */
    public function change(array $data)
    {
        if (!$this->canEdit) {
            return;
        }

        $this->value = isset($data['value']) ? $data['value'] : '';
    }

    /**
     * Получение значения в читаемом текстовом формате
     *
     * @param  bool  $fromModel
     * @return string
     */
    public function getValueText(bool $fromModel = false): string
    {
        $value = $fromModel ? $this->itemModelMapper?->get($this->name) : $this->value;

        if (!$value) {
            return '';
        }

        return (string)$value;
    }

    /**
     * Проверка на корректность значения
     *
     * @return array
     */
    public function check()
    {
        $this->errors = [];

        foreach ($this->validRules as $rule) {
            $errors = $rule->handle($this->df, $this->item, $this);
            $this->errors = array_merge($this->errors, $errors);
        }

        return $this->errors;
    }

    /**
     * Сохранение в базу с проверкой на корректность значения
     *
     * @return bool
     */
    public function save()
    {
        $errors = $this->check();

        if ($errors) {
            return false;
        }

        $data = [
            'item_id' => $this->itemId,
            'name' => $this->name,
            'data' => $this->data,
        ];

        if ($this->model) {
            $this->model->update($data);
        } else {
            $this->model = $this->df->fieldModelClass::create($data);
            $this->fill();
        }

        return true;
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
     * @return mixed
     */
    public function getItemModelMapper()
    {
        return $this->itemModelMapper;
    }

    /**
     * Установка карты модели заявки
     *
     * @param  ItemModelMapper|null  $itemModelMapper
     * @return void
     */
    public function setItemModelMapper(?ItemModelMapper $itemModelMapper)
    {
        $this->itemModelMapper = $itemModelMapper;
    }

    /**
     * Заполнение поля возможности редактирования
     *
     * @return void
     */
    public function fillCanEdit()
    {
        if (!$this->item) {
            return;
        }

        foreach ($this->editRules as &$editRule) {
            $this->canEdit = $editRule->handle($this->df, $this->item, $this);
            if (!$this->canEdit) {
                break;
            }
        }
    }

    public function fillItemModelMapper()
    {
        $this->itemModelMapper->set($this->name, $this->value);
    }


    protected DynamicForm $df;

    protected ?ItemModelMapper $itemModelMapper;

    protected ?Item $item = null;

    protected array $errors = [];

    protected array $validRules;

    protected array $editRules;

    /**
     * Создание экземпляра
     *
     * @param  array  $params
     */
    protected function __construct(array $params)
    {
        $this->handleParams($params);
        $this->fill();
        $this->fillValueText();
    }

    /**
     * Обработка параметров, переданных в конструктор
     *
     * @param  array  $params
     * @return void
     */
    protected function handleParams(array $params)
    {
        $this->df = $params['df'];
        $this->itemModelMapper = $params['modelMapper'];
        $this->name = $params['name'];
        $this->value = $params['value'];
    }

    /**
     * Заполнение всех полей
     *
     * @return void
     */
    protected function fill()
    {
        $fieldConfig = $this->df->config->getField($this->name);
        $this->id = $this->itemModelMapper ? $this->itemModelMapper->get('id') : null;
        $this->fillItemId();
        $this->title = $fieldConfig['title'];
        $this->type = $fieldConfig['type'];
        $this->visible = $fieldConfig['visible'];
        $this->tag = $fieldConfig['tag'];
        $this->validRules = $fieldConfig['validRules'];
        $this->editRules = $fieldConfig['editRules'];
        $this->fillValidRules();
        $this->fillEditRules();
    }

    /**
     * Заполнение значения в читаемом текстовом формате
     *
     * @param  bool  $fromModel
     * @return void]
     */
    protected function fillValueText(bool $fromModel = false)
    {
        $this->valueText = $this->getValueText($fromModel);
    }

    /**
     * Заполнение ID записи
     *
     * @return void
     */
    protected function fillItemId()
    {
        $this->itemId = $this->item ? $this->item->id : null;
    }

    /**
     * Заполнение правил проверки ввода значения
     *
     * @return void
     */
    protected function fillValidRules()
    {
        $rules = [];
        foreach ($this->validRules as &$rule) {
            $rule = $this->df->getValidRule($rule);
            if ($rule) {
                $rules[] = $rule;
            }
        }
        $this->validRules = $rules;
    }

    /**
     * Заполнение правил доступности редактирования поля
     *
     * @return void
     */
    protected function fillEditRules()
    {
        $this->canEdit = is_bool($this->editRules) ? $this->editRules : true;

        if (is_array($this->editRules)) {
            $editRules = [];
            foreach ($this->editRules as &$editRule) {
                $editRule = $this->df->getEditRule($editRule);
                if ($editRule) {
                    $editRules[] = $editRule;
                }
            }
            $this->editRules = $editRules;
        } else {
            $this->editRules = [];
        }
    }
}

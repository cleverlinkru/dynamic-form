<?php

namespace App\Services\DynamicForm;

use App\Models\User;

/**
 * Действие динамической формы
 */
class Action
{
    const TYPE_CREATE = 1;

    const TYPE_EDIT = 2;

    const TYPE_DELETE = 3;

    public $id;

    public $userId;

    public $user;

    public $itemId;

    public $type;

    public $data;

    public $createdText;

    /**
     * Получение экземпляра из модели
     *
     * @param  DynamicForm  $df
     * @param $model
     * @return self
     */
    public static function getInstanceWithModel(DynamicForm $df, $model)
    {
        return new self([
            'df' => $df,
            'model' => $model,
            'userId' => $model->user_id,
            'itemId' => $model->item_id,
            'type' => $model->type,
            'data' => $model->data,
        ]);
    }

    /**
     * Получение экземпляра из массива
     *
     * @param  DynamicForm  $df
     * @param  array  $data
     * @return self
     */
    public static function getInstanceWithArray(DynamicForm $df, array $data)
    {
        return new self([
            'df' => $df,
            'userId' => $data['userId'],
            'itemId' => $data['itemId'],
            'type' => $data['type'],
            'data' => $data['data'],
        ]);
    }

    /**
     * Проверка корректности значений
     *
     * @return bool
     */
    public function isCorrect()
    {
        return (bool) $this->data;
    }

    /**
     * Сохранение в базу
     *
     * @return void
     */
    public function save()
    {
        if (!$this->isCorrect()) {
            return;
        }

        $data = [
            'user_id' => $this->userId,
            'item_id' => $this->itemId,
            'type' => $this->type,
            'data' => $this->data,
        ];

        if ($this->model) {
            $this->model->update($data);
        } else {
            $this->model = $this->df->actionModelClass::create($data);
        }
    }


    protected DynamicForm $df;

    protected $model;

    /**
     * Создание экземпляра
     *
     * @param  array  $params
     */
    protected function __construct(array $params)
    {
        $this->df = $params['df'];
        $this->model = isset($params['model']) ? $params['model'] : null;
        $this->userId = $params['userId'];
        $this->itemId = $params['itemId'];
        $this->type = $params['type'];
        $this->data = $this->handleData($params['data']);

        $this->fillId();
        $this->fillUser();
        $this->fillCreatedText();
    }

    /**
     * Обработка значений
     *
     * @param  array  $data
     * @return array
     */
    protected function handleData(array $data)
    {
        $newData = [];
        foreach ($data as &$item) {
            if ($item['oldValue'] != $item['newValue']) {
                $newData[] = $item;
            }
        }
        return $newData;
    }

    /**
     * Заполнение ID
     *
     * @return void
     */
    protected function fillId()
    {
        $this->id = $this->model ? $this->model->id : null;
    }

    /**
     * Заполнение пользователя
     *
     * @return void
     */
    protected function fillUser()
    {
        $this->user = $this->userId ? User::findOrFail($this->userId) : null;
    }

    /**
     * Заполнение поля даты создания в текстовом формате
     *
     * @return void
     */
    protected function fillCreatedText()
    {
        $this->createdText = $this->model ? $this->model->created_at->format('d.m.Y H:i:s') : '';
    }
}

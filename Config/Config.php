<?php

namespace App\Services\DynamicForm\Config;

use App\Services\DynamicForm\Field;

/**
 * Объект конфигурация динамической формы
 */
class Config
{
    public $fields = [];

    /**
     * Получение массивов конфигураций всех полей
     *
     * @return array|mixed
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Получение массива конфигурации поля по его имени
     *
     * @param  string  $name
     * @return mixed|void
     */
    public function getField(string $name)
    {
        foreach ($this->fields as $field) {
            if ($field['name'] == $name) {
                return $field;
            }
        }
    }


    protected $view = null;

    /**
     * Создание экземпляра
     *
     * @param  string|null  $view
     */
    protected function __construct(string $view = null)
    {
        $this->view = $view;
        $this->handleFieldsView();
    }

    /**
     * Добавление конфигурации поля
     *
     * @param  array  $data
     * @return void
     * @throws \Exception
     */
    protected function addField(array $data)
    {
        $fieldClass = Field::getClass($data);
        $this->fields[] = array_merge(
            $fieldClass::handleConfig($data),
            [
                'views' => isset($data['views']) ? $data['views'] : [],
                'fieldClass' => $fieldClass,
                'sortable' => $fieldClass::$sortable,
                'filterable' => $fieldClass::$filterable,
            ],
        );
    }

    /**
     * Корректировка конфигураций всех полей на основании заданного вида
     *
     * @return void
     */
    protected function handleFieldsView()
    {
        $fields = [];
        foreach ($this->fields as &$field) {
            $newField = $this->handleFieldView($field);
            if ($newField) {
                $fields[] = $newField;
            }
        }
        $this->fields = $fields;
    }

    /**
     * Корректировка конфигураций поля на основании заданного вида
     *
     * @param  array  $field
     * @return array|null
     */
    protected function handleFieldView(array $field): ?array
    {
        if ($this->view && isset($field['views'][$this->view])) {
            $viewParams = $field['views'][$this->view];

            if (in_array('remove', $viewParams)) {
                return null;
            }

            $field = array_merge($field, $viewParams);
        }

        unset($field['views']);

        return $field;
    }
}

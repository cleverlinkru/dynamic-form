<?php

namespace App\Services\DynamicForm\Config;

/**
 * Реализация конфигурации динамической формы на основании массива
 */
class ArrayConfig extends Config
{
    /**
     * Создание экземпляра
     *
     * @param  array  $data
     * @param  string|null  $view
     * @throws \Exception
     */
    public function __construct(array $data, string $view = null)
    {
        foreach ($data as $fieldData) {
            $this->addField($fieldData);
        }
        parent::__construct($view);
    }
}

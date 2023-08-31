<?php

namespace App\Services\DynamicForm\Fields;

use App\Services\DynamicForm\Field;
use App\Services\DynamicForm\Traits\SelectTrait;

/**
 * Поле динамической формы. Выпадающий список
 */
class Select extends Field
{
    use SelectTrait;

    /**
     * Обработка конфигурационного массива
     *
     * @param  array  $data
     * @return []|array
     */
    public static function handleConfig(array $data)
    {
        return array_merge(
            parent::handleConfig($data),
            [
                'options' => $data['options'],
                'multiple' => isset($data['multiple']) ? $data['multiple'] : false,
                'filterMultiple' => isset($data['filterMultiple']) ? $data['filterMultiple'] : false,
            ]
        );
    }
}

<?php

namespace App\Services\DynamicForm\ValidRules;

use App\Services\DynamicForm\DynamicForm;
use App\Services\DynamicForm\Field;
use App\Services\DynamicForm\Item;

/**
 * Реализация правила проверки ввода значения поля. Значение поля должно быть задано обязательно.
 */
class RequiredValidRule extends ValidRule
{
    /**
     * Определение корректности значения поля с возвратом массива ошибок
     *
     * @param  DynamicForm  $df
     * @param  Item  $item
     * @param  Field  $field
     * @return array|string[]
     */
    public function handle(DynamicForm $df, Item $item, Field $field): array
    {
        if (!$field->data['value']) {
            return ['Обязательно'];
        }
        return [];
    }
}

<?php

namespace App\Services\DynamicForm\ValidRules;

use App\Services\DynamicForm\DynamicForm;
use App\Services\DynamicForm\Field;
use App\Services\DynamicForm\Item;

/**
 * Объект правила проверки ввода значения поля
 */
class ValidRule
{
    /**
     * Определение корректности значения поля с возвратом массива ошибок
     *
     * @param  DynamicForm  $df
     * @param  Item  $item
     * @param  Field  $field
     * @return array
     */
    public function handle(DynamicForm $df, Item $item, Field $field): array
    {
        return [];
    }
}

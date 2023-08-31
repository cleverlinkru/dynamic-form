<?php

namespace App\Services\DynamicForm\EditRules;

use App\Services\DynamicForm\DynamicForm;
use App\Services\DynamicForm\Field;
use App\Services\DynamicForm\Item;

/**
 * Объект правила доступности редактирования поля
 */
abstract class EditRule
{
    /**
     * Определение доступности редактирования поля
     *
     * @param  DynamicForm  $df
     * @param  Item  $item
     * @param  Field  $field
     * @return bool
     */
    abstract public function handle(DynamicForm $df, Item $item, Field $field): bool;
}

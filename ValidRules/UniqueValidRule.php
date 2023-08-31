<?php

namespace App\Services\DynamicForm\ValidRules;

use App\Services\DynamicForm\DynamicForm;
use App\Services\DynamicForm\Field;
use App\Services\DynamicForm\Item;

/**
 * Реализация правила проверки ввода значения поля. Уникальное значение поля среди всех записей.
 */
class UniqueValidRule extends ValidRule
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
        $query = $df->fieldModelClass::query()
            ->where('name', $field->name)
            ->whereJsonContains('data', ['value' => $field->data['value']])
            ->when($field->id, function ($q) use ($field) {
                $q->where('id', '<>', $field->id);
            });
        $df->fieldQuery($query);
        $count = $query->count();
        if ($count) {
            return ['Должно быть уникальным'];
        }
        return [];
    }
}

<?php

namespace App\Services\DynamicForm;

class ItemRequestRules
{
    /**
     * Получение массива правил проверки ввода для поля с одной записью
     *
     * @param  bool  $required
     * @param  string  $fieldName
     * @return array
     */
    public function get(bool $required = true, string $fieldName = 'item'): array
    {
        return [
            "$fieldName" => array_merge(
                ($required ? ['required'] : ['nullable']),
                ['array'],
            ),
            "$fieldName.fields" => [
                ($required ? ['required'] : ['nullable']),
                'array',
            ],
            "$fieldName.fields.*.name" => ['required', 'string'],
            "$fieldName.fields.*.data" => ['nullable', 'array'],
        ];
    }
}

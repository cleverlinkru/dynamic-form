<?php

namespace App\Services\DynamicForm\Fields;

use DateTime;
use Illuminate\Database\Eloquent\Builder;

class ItemCreated extends Meta
{
    public static function handleFilter(Builder $query, array $configField, $value)
    {
        if (
            (!isset($value['from']) || !$value['from']) &&
            (!isset($value['to']) || !$value['to'])
        ) {
            return;
        }

        $from = $value['from'] ? new DateTime($value['from']) : null;
        $to = $value['to'] ? new DateTime($value['to']) : null;

        if ($from) {
            $query->where('created_at', '>=', $from->format('Y-m-d H:i:s'));
        }
        if ($to) {
            $query->where('created_at', '<=', $to->format('Y-m-d H:i:s'));
        }
    }

    protected function fillData()
    {
        $this->data = [
            'value' => $this->item ? $this->item->createdText : '',
        ];
    }
}

<?php

namespace App\Services\DynamicForm\Fields;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ItemUser extends Meta
{
    public $onFilterSelect;

    public $options;

    public static function handleConfig(array $data)
    {
        $options = [];
        $users = User::orderBy('name')->get();
        foreach ($users as $user) {
            $options[] = [
                'title' => $user->name,
                'value' => $user->id,
            ];
        }

        return array_merge(
            parent::handleConfig($data),
            [
                'onFilterSelect' => isset($data['onFilterSelect']) ? $data['onFilterSelect'] : false,
                'options' => $options,
            ]
        );
    }

    public static function handleFilter(Builder $query, array $configField, $value)
    {
        if (!$value) {
            return;
        }

        if ($configField['onFilterSelect']) {
            $query->whereHas('user', function ($query) use ($value) {
                $query->whereIn('id', $value);
            });
        } else {
            $search = mb_strtolower($value);
            $query->whereHas('user', function ($query) use ($search) {
                $query->whereRaw("lower(name) like '%".$search."%'");
            });
        }
    }


    protected function fill()
    {
        parent::fill();
        $fieldConfig = $this->df->config->getField($this->name);
        $this->onFilterSelect = $fieldConfig['onFilterSelect'];
        $this->options = $fieldConfig['options'];
    }

    protected function fillData()
    {
        $this->data = [
            'value' => $this->item ? $this->item->userName : '',
        ];
    }
}

<?php

namespace App\Services\DynamicForm\Traits;

use Illuminate\Database\Eloquent\Builder;

trait SelectTrait
{
    public $options;

    public $multiple;

    public static function handleFilter(Builder $query, array $configField, $value)
    {
        if (!$value) {
            return;
        }

        $query->whereHas('fields', function ($query) use ($configField, $value) {
            $query->where('name', $configField['name']);
            if ($configField['multiple']) {
                $query->whereJsonContains('data->value', $value);
            } else {
                if ($configField['filterMultiple']) {
                    $query->whereIn('data->value', $value);
                } else {
                    $query->where('data->value', $value);
                }
            }
        });
    }

    public function getValueText(bool $fromModel = false): string
    {
        $data = $fromModel ? $this->model?->data : $this->data;

        if (!$data) {
            return '';
        }

        $this->prepareValue($data['value']);

        if ($this->multiple) {
            $res = [];
            foreach ($this->options as $option) {
                if (in_array($option['value'], $data['value'])) {
                    $res[] = $option['title'];
                }
            }
            asort($res);
            $res = implode(",\n", $res);
            return $res;
        } else {
            foreach ($this->options as $option) {
                if ($option['value'] == $data['value']) {
                    return $option['title'];
                }
            }
        }

        return '';
    }


    protected function fill()
    {
        parent::fill();
        $fieldConfig = $this->df->config->getField($this->name);
        $this->options = $fieldConfig['options'];
        $this->multiple = $fieldConfig['multiple'];
        $this->sortOptions();
        $this->prepareValue($this->data['value']);
    }

    protected function prepareValue(&$value)
    {
        if ($this->multiple) {
            if (!is_array($value)) {
                $value = $value ? [$value] : [];
            }
        } else {
            if (is_array($value)) {
                $value = isset($value[0]) ? $value[0] : '';
            }
        }
    }

    protected function sortOptions()
    {
        usort($this->options, function ($a, $b) {
            return $a['title'] > $b['title'];
        });
    }
}

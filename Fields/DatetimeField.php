<?php

namespace App\Services\DynamicForm\Fields;

use App\Services\DynamicForm\Field;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class DatetimeField extends Field
{
    public $showTime;

    public static function handleConfig(array $data)
    {
        return array_merge(
            parent::handleConfig($data),
            [
                'showTime' => isset($data['showTime']) ? $data['showTime'] : true,
            ]
        );
    }

    public static function handleFilter(Builder $query, array $configField, $value)
    {
        if (
            (!isset($value['from']) || !$value['from']) &&
            (!isset($value['to']) || !$value['to'])
        ) {
            return;
        }

        $from = $value['from'] ? (new Carbon($value['from'])) : null;
        $to = $value['to'] ? (new Carbon($value['to'])) : null;

        $query->whereHas('fields', function ($query) use ($configField, $from, $to) {
            $query->where('name', $configField['name']);
            if ($from) {
                $query->where('data->value', '>=', $from->format('Y-m-d H:i:s'));
            }
            if ($to) {
                $query->where('data->value', '<=', $to->format('Y-m-d H:i:s'));
            }
        });
    }

    public function change(array $data)
    {
        if (!$this->canEdit) {
            return;
        }

        if (isset($data['data']['value'])) {
            $datetime = new Carbon($data['data']['value']);
            $datetime->tz(config('app.timezone'));
            if (!$this->showTime) {
                $datetime->setTime(0, 0, 0);
            }
            $this->data['value'] = $datetime->format('Y-m-d H:i:s');
        } else {
            $this->data['value'] = '';
        }
    }

    public function getValueText(bool $fromModel = false): string
    {
        $data = $fromModel ? $this->model?->data : $this->data;

        if (!$data) {
            return '';
        }

        if ($data['value']) {
            $datetime = (new Carbon($data['value']))->tz(config('app.timezone'));
            $format = 'd.m.Y'.($this->showTime ? ' H:i:s' : '');
            return $datetime->format($format);
        }

        return '';
    }


    protected function fill()
    {
        parent::fill();
        $fieldConfig = $this->df->config->getField($this->name);
        $this->showTime = $fieldConfig['showTime'];
        $this->fillData();
    }

    protected function fillData()
    {
        if ($this->data['value']) {
            $datetime = (new Carbon($this->data['value']))->tz(config('app.timezone'));
            if (!$this->showTime) {
                $datetime->setTime(0, 0, 0);
            }
            $this->data['value'] = $datetime->format('Y-m-d H:i:s');
        }
    }
}

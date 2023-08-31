<?php

namespace App\Services\DynamicForm\Fields;

use App\Services\Division\Division;
use App\Services\DynamicForm\Field;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;

class Divisions extends Field
{
    public $divisions;

    public $multiple;

    public $showCode;

    public static $sortable = false;

    public static function handleConfig(array $data)
    {
        $divisionService = App::make(Division::class);

        return array_merge(
            parent::handleConfig($data),
            [
                'multiple' => isset($data['multiple']) ? $data['multiple'] : false,
                'showCode' => isset($data['showCode']) ? $data['showCode'] : true,
                'districts' => $divisionService->getDistricts(),
                'affiliates' => $divisionService->getAffiliates(),
                'formats' => $divisionService->getFormats(),
                'subformats' => $divisionService->getSubformats(),
            ]
        );
    }

    public static function handleFilter(Builder $query, array $configField, $value)
    {
        if (!$value) {
            return;
        }

        if (isset($value['districts']) && is_array($value['districts']) && $value['districts']) {
            $query->whereHas('fields', function ($query) use ($value) {
                $query->whereHas('divisions', function ($query) use ($value) {
                    $query->whereHas('corpRegion', function ($query) use ($value) {
                        $query->whereHas('parent', function ($query) use ($value) {
                            $query->whereHas('parent', function ($query) use ($value) {
                                $query->whereIn('guid', $value['districts']);
                            });
                        });
                    });
                });
            });
        }

        if (isset($value['affiliates']) && is_array($value['affiliates']) && $value['affiliates']) {
            $query->whereHas('fields', function ($query) use ($value) {
                $query->whereHas('divisions', function ($query) use ($value) {
                    $query->whereHas('corpRegion', function ($query) use ($value) {
                        $query->whereHas('parent', function ($query) use ($value) {
                            $query->whereIn('guid', $value['affiliates']);
                        });
                    });
                });
            });
        }

        if (isset($value['formats']) && is_array($value['formats']) && $value['formats']) {
            $query->whereHas('fields', function ($query) use ($value) {
                $query->whereHas('divisions', function ($query) use ($value) {
                    $query->whereHas('format', function ($query) use ($value) {
                        $query->whereHas('parent', function ($query) use ($value) {
                            $query->whereIn('format_guid', $value['formats']);
                        });
                    });
                });
            });
        }

        if (isset($value['subformats']) && is_array($value['subformats']) && $value['subformats']) {
            $query->whereHas('fields', function ($query) use ($value) {
                $query->whereHas('divisions', function ($query) use ($value) {
                    $query->whereHas('format', function ($query) use ($value) {
                        $query->whereIn('format_guid', $value['subformats']);
                    });
                });
            });
        }
    }

    public function change(array $data)
    {
        if (!$this->multiple && count($data['data']['value']) > 1) {
            throw new Exception('Incorrect data');
        }

        parent::change($data);

        if ($this->canEdit) {
            $this->fillDivisions();
        }
    }

    public function getValueText(bool $fromModel = false): string
    {
        $divisions = $this->getDivisions($fromModel);
        $text = '';
        foreach ($divisions as $division) {
            if ($text) {
                $text .= ",\n";
            }
            $text .= ($this->showCode ? $division->code.' ' : '').$division->name;
        }

        return $text;
    }


    protected $divisionService;

    protected function fill()
    {
        parent::fill();
        $fieldConfig = $this->df->config->getField($this->name);
        $this->divisionService = App::make(Division::class);
        $this->multiple = $fieldConfig['multiple'];
        $this->showCode = $fieldConfig['showCode'];

        if ($this->data['value'] && (count($this->data['value']) > 1)) {
            $this->data['value'] = [$this->data['value'][0]];
        }

        $this->fillDivisions();
    }

    protected function fillDivisions()
    {
        $this->divisions = $this->getDivisions();

        $divisionsIds = [];
        foreach ($this->divisions as $division) {
            $divisionsIds[] = $division->id;
        }
        $this->data = [
            'value' => $divisionsIds,
        ];
    }

    protected function getDivisions(bool $fromModel = false)
    {
        if ($fromModel) {
            $divisionsIds = $this->model
                ? (is_array($this->model->data['value']) ? $this->model->data['value'] : [])
                : [];
        } else {
            $divisionsIds = is_array($this->data['value']) ? $this->data['value'] : [];
        }
        return $divisionsIds ? $this->divisionService->getitems(['ids' => $divisionsIds]) : [];
    }
}

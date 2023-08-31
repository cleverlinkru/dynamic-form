<?php

namespace App\Services\DynamicForm\Fields;

use App\Services\Catalog\Catalog as CatalogService;
use App\Services\DynamicForm\Field;
use App\Services\DynamicForm\Traits\SelectTrait;
use Illuminate\Support\Facades\App;

class Catalog extends Field
{
    use SelectTrait;

    public static function handleConfig(array $data)
    {
        return array_merge(
            parent::handleConfig($data),
            [
                'options' => self::getOptions($data),
                'multiple' => isset($data['multiple']) ? $data['multiple'] : false,
                'filterMultiple' => isset($data['filterMultiple']) ? $data['filterMultiple'] : false,
                'categoryId' => $data['categoryId'],
                'titleField' => $data['titleField'],
            ]
        );
    }


    protected static function getOptions(array $fieldConfig): array
    {
        $options = [];
        $catalogService = App::make(CatalogService::class);
        $itemsRes = $catalogService->getItems($fieldConfig['categoryId'], null, null, $fieldConfig['titleField']);
        foreach ($itemsRes['items'] as $item) {
            $titleField = $item->getField($fieldConfig['titleField']);
            if ($titleField) {
                $options[] = [
                    'title' => $titleField->data['value'],
                    'value' => $item->id,
                ];
            }
        }
        return $options;
    }
}

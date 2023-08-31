<?php

namespace App\Services\DynamicForm\Fields;

use App\Services\DynamicForm\Field;
use App\Services\DynamicForm\Item;

abstract class Meta extends Field
{
    public function setItem(Item $item)
    {
        parent::setItem($item);
        $this->fillData();
        $this->fillValueText();
    }

    public function change(array $data)
    {
    }

    public function getValueText(bool $fromModel = false): string
    {
        return (string)$this->data['value'];
    }

    public function check()
    {
        return [];
    }

    public function save()
    {
        return true;
    }

    public function delete()
    {
    }

    public function fillCanEdit()
    {
        $this->canEdit = false;
    }


    protected function fill()
    {
        parent::fill();
        $this->fillData();
    }

    protected abstract function fillData();
}

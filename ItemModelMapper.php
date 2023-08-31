<?php

namespace App\Services\DynamicForm;


use Illuminate\Database\Eloquent\Builder;

abstract class ItemModelMapper
{
    public function __construct($model = null)
    {
        if ($model) {
            $this->model = $model;
            $this->isSaved = true;
        } else {
            $this->model = new static::$itemModelClass();
            $this->isSaved = false;
        }
    }

    public function query(): Builder
    {
        return static::$itemModelClass::query();
    }

    public function search(Builder &$query, string $text)
    {
    }

    public function whereLike(Builder &$query, string $fieldName, $value)
    {
    }

    public function order(Builder &$query, string $fieldName, bool $dir)
    {
        $query->orderBy($fieldName, $dir ? 'asc' : 'desc');
    }

    public function get(string $fieldName)
    {
        return $this->model->{$fieldName};
    }

    public function set(string $fieldName, $value)
    {
        $this->model->{$fieldName} = $value;
    }

    public function save()
    {
        $res = $this->model->save();
        if ($res) {
            $this->isSaved = true;
        }
    }

    public function delete()
    {
        $this->model->delete();
        $this->model = null;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function isSaved()
    {
        return $this->isSaved;
    }


    protected static string $itemModelClass = '';

    protected $model = null;

    protected bool $isSaved;
}

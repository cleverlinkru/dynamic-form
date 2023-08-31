<?php

namespace App\Services\DynamicForm\Fields;

use App\Services\DynamicForm\Field;
use App\Services\File\File;
use Illuminate\Support\Facades\App;

class Files extends Field
{
    public $files;

    public $uploadRoute;

    public $downloadRoute;

    public static function handleConfig(array $data)
    {
        return array_merge(
            parent::handleConfig($data),
            [
                'uploadRoute' => $data['uploadRoute'],
                'downloadRoute' => $data['downloadRoute'],
            ]
        );
    }

    public function getValueText(bool $fromModel = false): string
    {
        $data = $fromModel ? $this->model?->data : $this->data;

        if (!$data) {
            return '';
        }

        $text = '';
        $files = $data['value'] ? $this->fileService->getList(['ids' => $data['value']]) : [];
        foreach ($files as $file) {
            if ($text) {
                $text .= "\n";
            }
            $text .= $file->name;
        }

        return $text;
    }

    public function save()
    {
        $isNew = !$this->model;
        $prevFiles = $this->files;

        $res = parent::save();

        if (!$res) {
            return false;
        }

        $this->fillFiles();
        $curFiles = $this->files;

        if ($isNew) {
            $this->fileService->fix($curFiles);
        } else {
            $this->fileService->replace($prevFiles, $curFiles);
        }

        return true;
    }


    protected $fileService;

    protected function fill()
    {
        parent::fill();
        $this->fileService = App::make(File::class);
        $fieldConfig = $this->df->config->getField($this->name);
        $this->uploadRoute = $fieldConfig['uploadRoute'];
        $this->downloadRoute = $fieldConfig['downloadRoute'];
        $this->fillFiles();
    }

    protected function fillFiles()
    {
        $this->files = $this->data['value'] ? $this->fileService->getList(['ids' => $this->data['value']]) : [];
    }
}

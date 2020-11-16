<?php

namespace ManaPHP\Data\Model;

class NotFoundException extends Exception
{
    /**
     * @var string
     */
    public $model;

    /**
     * @var int|string|array
     */
    public $filters;

    public function __construct($model, $filters)
    {
        parent::__construct(['No record for `%s` model of `%s`', $model, json_stringify($filters)]);

        $this->model = $model;
        $this->filters = $filters;
    }

    public function getStatusCode()
    {
        return 404;
    }

    public function getJson()
    {
        return ['code' => 404, 'message' => "Record of `$this->model` Model is not exists"];
    }
}
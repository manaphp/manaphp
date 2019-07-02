<?php
namespace ManaPHP\Model;

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
        parent::__construct(['No record for `:model` model of `:filters`',
            'model' => $model,
            'filters' => json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);

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
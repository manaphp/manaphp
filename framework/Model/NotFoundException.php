<?php
declare(strict_types=1);

namespace ManaPHP\Model;

use JetBrains\PhpStorm\ArrayShape;

class NotFoundException extends Exception
{
    public string $model;
    public mixed $filters;

    public function __construct(string $model, mixed $filters)
    {
        parent::__construct(['No record for `{1}` model of `{2}`', $model, json_stringify($filters)]);

        $this->model = $model;
        $this->filters = $filters;
    }

    public function getStatusCode(): int
    {
        return 404;
    }

    #[ArrayShape(['code' => "int", 'message' => "string"])]
    public function getJson(): array
    {
        return ['code' => 404, 'message' => "Record of `$this->model` Model is not exists"];
    }
}
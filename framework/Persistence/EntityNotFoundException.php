<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use JetBrains\PhpStorm\ArrayShape;
use function json_stringify;

class EntityNotFoundException extends Exception
{
    public string $entityClass;
    public mixed $filters;

    public function __construct(string $entityClass, mixed $filters)
    {
        parent::__construct(['No record for `{1}` entity of `{2}`', $entityClass, json_stringify($filters)]);

        $this->entityClass = $entityClass;
        $this->filters = $filters;
    }

    public function getStatusCode(): int
    {
        return 404;
    }

    #[ArrayShape(['code' => 'int', 'msg' => 'string'])]
    public function getJson(): array
    {
        return ['code' => 404, 'msg' => "Record of `$this->entityClass` Model is not exists"];
    }
}
<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

interface CriteriaInterface
{
    public static function select(array $fields = []): static;

    public function where($filters): static;

    public function getWhere(): array;

    public function getSelect(): array;

    public function orderBy(array $order): static;

    public function getOrderBy(): array;

    public function getLimit(): ?int;

    public function getPage(): ?int;

    public function page(int $page, int $limit): static;
}
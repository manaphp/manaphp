<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

class Criteria implements CriteriaInterface
{
    protected array $select = [];

    protected array $where = [];

    protected array $order = [];

    protected ?int $page;

    protected ?int $limit;

    public static function select(array $fields = []): static
    {
        $criteria = new static();
        $criteria->select = $fields;

        return $criteria;
    }

    public function where($filters): static
    {
        if ($this->where === []) {
            $this->where = $filters;
        } else {
            $this->where += $filters;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getWhere(): array
    {
        return $this->where;
    }

    public function getSelect(): array
    {
        return $this->select;
    }

    public function orderBy(array $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getOrderBy(): array
    {
        return $this->order;
    }

    /**
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function page(int $page, int $limit): static
    {
        $this->page = $page;
        $this->limit = $limit;

        return $this;
    }
}
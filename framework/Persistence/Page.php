<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

class Page
{
    protected int $page;
    protected int $limit;

    public static function of(int $page, int $limit): static
    {
        $instance = new static();

        $instance->page = $page;
        $instance->limit = $limit;

        return $instance;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}
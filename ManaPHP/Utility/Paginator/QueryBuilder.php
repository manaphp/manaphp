<?php
namespace ManaPHP\Utility\Paginator;

use ManaPHP\Utility\Paginator;

class QueryBuilder extends Paginator
{
    /**
     * QueryBuilder constructor.
     *
     * @param \ManaPHP\Mvc\Model\QueryBuilderInterface $builder
     * @param int                                      $size
     * @param int                                      $page
     */
    public function __construct($builder, $size, $page = null)
    {
        $this->size = $size;
        $this->page = $page ? max(1, $page) : 1;

        $this->items = $builder->limit($this->size, ($this->page - 1) * $this->size)
            ->executeEx($this->count);

        $this->pages = ceil($this->count / $size);
    }
}
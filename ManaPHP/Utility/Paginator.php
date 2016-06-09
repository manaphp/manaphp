<?php
namespace ManaPHP\Utility {

    class Paginator
    {
        /**
         * @var int
         */
        public $size;

        /**
         * @var int
         */
        public $page;

        /**
         * @var array
         */
        public $items;

        /**
         * @var int
         */
        public $count;

        /**
         * @var int
         */
        public $pages;

        public function getPaginate()
        {
            $paginate = new \stdClass();

            $paginate->items = $this->items;
            $paginate->first = 1;
            $paginate->before = max(1, $this->page - 1);
            $paginate->current = $this->page;
            $paginate->last = $this->pages;
            $paginate->next = min($this->page + 1, $this->pages);
            $paginate->pages = $this->pages;
            $paginate->count = $this->count;
            $paginate->size = $this->size;

            return $paginate;
        }
    }
}
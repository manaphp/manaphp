<?php
namespace ManaPHP {

    class Paginator
    {
        public $first;
        public $before;
        public $current;
        public $last;
        public $next;
        public $total_pages;
        public $total_items;
        public $limit;

        /**
         * @param int $total_items
         * @param int $limit
         * @param int $current
         *
         * @return static
         */
        public function calc($total_items, $limit, $current)
        {
            $totalPages = ceil($total_items / $limit);

            $this->first = 1;
            $this->before = max(1, $current - 1);
            $this->current = $current;
            $this->last = $totalPages;
            $this->next = min($current + 1, $totalPages);
            $this->total_pages = $totalPages;
            $this->total_items = $total_items;
            $this->limit = $limit;

            return $this;
        }
    }
}
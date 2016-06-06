<?php
namespace ManaPHP\Utility {

    class Paginator
    {
        public $first;
        public $before;
        public $current;
        public $last;
        public $next;
        public $totalPages;
        public $totalItems;
        public $limit;

        /**
         * @param int $totalItems
         * @param int $limit
         * @param int $current
         *
         * @return static
         */
        public function calc($totalItems, $limit, $current)
        {
            $totalPages = ceil($totalItems / $limit);

            $this->first = 1;
            $this->before = max(1, $current - 1);
            $this->current = $current;
            $this->last = $totalPages;
            $this->next = min($current + 1, $totalPages);
            $this->totalPages = $totalPages;
            $this->totalItems = $totalItems;
            $this->limit = $limit;

            return $this;
        }
    }
}
<?php
declare(strict_types=1);

namespace ManaPHP\Data\Query;

use ManaPHP\Data\PaginatorInterface;

interface PaginatorMakerInterface
{
    public function make(): PaginatorInterface;
}
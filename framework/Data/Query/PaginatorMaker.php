<?php
declare(strict_types=1);

namespace ManaPHP\Data\Query;

use ManaPHP\Data\PaginatorInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;

class PaginatorMaker implements PaginatorMakerInterface
{
    #[Inject] protected MakerInterface $maker;

    public function make(): PaginatorInterface
    {
        return $this->maker->make(PaginatorInterface::class);
    }
}
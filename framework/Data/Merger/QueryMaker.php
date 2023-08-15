<?php
declare(strict_types=1);

namespace ManaPHP\Data\Merger;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;

class QueryMaker implements QueryMakerInterface
{
    #[Inject] protected MakerInterface $maker;

    public function make(string $query): mixed
    {
        return $this->maker->make($query);
    }
}
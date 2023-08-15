<?php
declare(strict_types=1);

namespace ManaPHP\Html\Dom;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;

class QueryMaker implements QueryMakerInterface
{
    #[Inject] protected MakerInterface $maker;

    public function make(array $parameters): mixed
    {
        return $this->maker->make(Query::class, $parameters);
    }
}
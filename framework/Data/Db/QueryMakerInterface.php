<?php
declare(strict_types=1);

namespace ManaPHP\Data;

use ManaPHP\Data\Db\Query;

interface QueryMakerInterface
{
    public function make(array $parameters): Query;
}
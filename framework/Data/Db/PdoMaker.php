<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;

class PdoMaker implements PdoMakerInterface
{
    #[Inject] protected MakerInterface $maker;

    public function make(array $parameters): mixed
    {
        return $this->maker->make('PDO', $parameters);
    }
}
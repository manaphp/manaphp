<?php
declare(strict_types=1);

namespace ManaPHP\Data\Redis\Connection;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;

class RedisMaker implements RedisMakerInterface
{
    #[Inject] protected MakerInterface $maker;

    public function make(string $class, array $parameters = []): mixed
    {
        return $this->maker->make($class, $parameters);
    }
}
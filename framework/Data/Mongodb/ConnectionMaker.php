<?php
declare(strict_types=1);

namespace ManaPHP\Data\Mongodb;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;

class ConnectionMaker implements ConnectionMakerInterface
{
    #[Inject] protected MakerInterface $maker;

    public function make(array $parameters): mixed
    {
        return $this->maker->make(Connection::class, $parameters);
    }
}
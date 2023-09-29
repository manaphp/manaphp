<?php
declare(strict_types=1);

namespace ManaPHP\Amqp;

use ManaPHP\Amqp\Engine\Php;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\MakerInterface;

class Engine
{
    #[Autowired] protected MakerInterface $maker;

    public function __invoke(array $parameters, ?string $id): mixed
    {
        return $this->maker->make(Php::class, $parameters, $id);
    }
}
<?php
declare(strict_types=1);

namespace ManaPHP\Amqp;

use ManaPHP\Amqp\Engine\Php;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;

class Engine
{
    #[Inject] protected MakerInterface $maker;

    public function __invoke(array $parameters): mixed
    {
        return $this->maker->make(Php::class, $parameters);
    }
}
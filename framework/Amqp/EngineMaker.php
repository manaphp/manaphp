<?php
declare(strict_types=1);

namespace ManaPHP\Amqp;

use ManaPHP\Amqp\Engine\Php;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;

class EngineMaker implements EngineMakerInterface
{
    #[Inject] protected MakerInterface $maker;

    public function make(array $parameters): mixed
    {
        return $this->maker->make(Php::class, $parameters);
    }
}
<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Client;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;

class EngineMaker implements EngineMakerInterface
{
    #[Inject] protected MakerInterface $maker;

    public function make(array $parameters): mixed
    {
        return $this->maker->make(Engine::class, $parameters);
    }
}
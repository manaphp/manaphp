<?php
declare(strict_types=1);

namespace ManaPHP\Http\Client;


use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Http\Client\Engine\Fopen;

class Engine
{
    #[Autowired] protected MakerInterface $maker;

    public function __invoke(array $parameters, ?string $id): mixed
    {
        return $this->maker->make(Fopen::class, $parameters, $id);
    }
}
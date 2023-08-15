<?php
declare(strict_types=1);

namespace ManaPHP\Ws;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Ws\Server\Adapter\Swoole;

class ServerFactory
{
    #[Inject] protected MakerInterface $maker;

    public function __invoke(array $parameters, ?string $id)
    {
        return $this->maker->make(Swoole::class, $parameters, $id);
    }
}
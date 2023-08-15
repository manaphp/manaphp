<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Http\Session\Adapter\Redis;

class SessionFactory
{
    #[Inject] protected MakerInterface $maker;

    public function __invoke(array $parameters, ?string $id)
    {
        return $this->maker->make(Redis::class, $parameters, $id);
    }
}
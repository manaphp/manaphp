<?php
declare(strict_types=1);

namespace ManaPHP\Db;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;

class Connection
{
    #[Inject] protected MakerInterface $maker;

    public function __invoke(array $parameters, ?string $id): mixed
    {
        $uri = $parameters['0'] ?? $parameters['uri'];
        $adapter = 'ManaPHP\Db\Connection\Adapter\\' . ucfirst(parse_url($uri, PHP_URL_SCHEME));

        return $this->maker->make($adapter, $parameters, $id);
    }
}
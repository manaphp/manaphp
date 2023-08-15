<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;

class ConnectionMaker implements ConnectionMakerInterface
{
    #[Inject] protected MakerInterface $maker;

    public function make(array $parameters): mixed
    {
        $adapter = 'ManaPHP\Data\Db\Connection\Adapter\\' . ucfirst(parse_url($parameters[0], PHP_URL_SCHEME));
        return $this->maker->make($adapter, $parameters);
    }
}
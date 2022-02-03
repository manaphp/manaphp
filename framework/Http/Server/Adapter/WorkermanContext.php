<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter;

use Workerman\Connection\ConnectionInterface;

class WorkermanContext
{
    public ConnectionInterface $connection;
}

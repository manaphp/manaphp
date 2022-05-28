<?php
declare(strict_types=1);

namespace ManaPHP\Logging;

class AbstractLoggerContext
{
    public string $client_ip;
    public string $request_id;
}
<?php

namespace ManaPHP\Logging;

class AbstractLoggerContext
{
    /**
     * @var int
     */
    public $level;

    /**
     * @var string
     */
    public $client_ip;

    /**
     * @var string
     */
    public $request_id;
}
<?php

namespace ManaPHP\Logging;

class LoggerContext
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
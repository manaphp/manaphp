<?php

namespace ManaPHP\Http\Client;

use JsonSerializable;

class Request implements JsonSerializable
{
    /**
     * @var string
     */
    public $method;

    /**
     * @var string
     */
    public $url;

    /**
     * @var array
     */
    public $headers;

    /**
     * @var string|array
     */
    public $body;

    /**
     * @var array
     */
    public $options;

    /**
     * @var float
     */
    public $process_time;

    /**
     * @var string
     */
    public $remote_ip;

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
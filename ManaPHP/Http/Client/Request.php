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

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
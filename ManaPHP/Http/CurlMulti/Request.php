<?php

namespace ManaPHP\Http\CurlMulti;

class Request
{
    /**
     * @var string
     */
    public $method = 'GET';

    /**
     * @var string|array
     */
    public $url;

    /**
     * @var array|string
     */
    public $body = [];

    /**
     * @var array
     */
    public $headers = [];

    /**
     * @var array
     */
    public $options = [];

    /**
     * @var array
     */
    public $meta = [];

    /**
     * @var array
     */
    public $callbacks;

    /**
     * @var float
     */
    public $start_time;

    /**
     * Request constructor.
     *
     * @param string|array $url
     * @param callable     $callbacks
     * @param string       $method
     * @param string|array $body
     */
    public function __construct($url, $callbacks = null, $method = 'GET', $body = null)
    {
        $this->url = $url;
        $this->callbacks = $callbacks;
        $this->method = $method;
        $this->body = $body;
    }
}
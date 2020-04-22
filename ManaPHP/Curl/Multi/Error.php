<?php

namespace ManaPHP\Curl\Multi;

class Error
{
    /**
     * @var int
     */
    public $code;

    /**
     * @var string
     */
    public $message;

    /**
     * @var \ManaPHP\Curl\Multi\Request
     */
    public $request;
}
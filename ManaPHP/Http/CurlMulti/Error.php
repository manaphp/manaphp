<?php

namespace ManaPHP\Http\CurlMulti;

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
     * @var \ManaPHP\Http\CurlMulti\Request
     */
    public $request;
}
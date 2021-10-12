<?php

namespace ManaPHP\Http;

use ManaPHP\Coroutine\Context\Stickyable;

class RequestContext implements Stickyable
{
    public $request_id;

    /**
     * @var array
     */
    public $_GET = [];

    /**
     * @var array
     */
    public $_POST = [];

    /**
     * @var array
     */
    public $_REQUEST = [];

    /**
     * @var array
     */
    public $_SERVER = [];

    /**
     * @var array
     */
    public $_COOKIE = [];

    /**
     * @var array
     */
    public $_FILES = [];

    public $rawBody;
}
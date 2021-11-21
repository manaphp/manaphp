<?php

namespace ManaPHP\Http;

use ManaPHP\Coroutine\Context\Stickyable;

class GlobalsContext implements Stickyable
{
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

    /**
     * @var string
     */
    public $rawBody;
}
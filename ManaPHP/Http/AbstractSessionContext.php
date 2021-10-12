<?php

namespace ManaPHP\Http;

class AbstractSessionContext
{
    /**
     * @var int
     */
    public $ttl;

    /**
     * @var bool
     */
    public $started = false;

    /**
     * @var bool
     */
    public $is_new;

    /**
     * @var bool
     */
    public $is_dirty = false;

    /**
     * @var string
     */
    public $session_id;

    /**
     * @var array
     */
    public $_SESSION;
}
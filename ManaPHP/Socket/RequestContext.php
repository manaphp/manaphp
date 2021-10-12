<?php

namespace ManaPHP\Socket;

class RequestContext
{
    /**
     * @var string
     */
    public $request_id;

    /**
     * @var array
     */
    public $_REQUEST = [];

    /**
     * @var array
     */
    public $_SERVER = [];

    public function __construct()
    {
        $this->request_id = 'aa' . bin2hex(random_bytes(15));
    }
}
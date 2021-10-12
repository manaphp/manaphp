<?php

namespace ManaPHP\Http;

class ResponseContext
{
    /**
     * @var int
     */
    public $status_code = 200;

    /**
     * @var string
     */
    public $status_text = 'OK';

    /**
     * @var array
     */
    public $headers = [];

    /**
     * @var array
     */
    public $cookies = [];

    /**
     * @var mixed
     */
    public $content = '';

    /**
     * @var string
     */
    public $file;
}
<?php

namespace ManaPHP\Http;

class DispatcherContext
{
    /**
     * @var string
     */
    public $path;

    /**
     * @var string
     */
    public $area;

    /**
     * @var string
     */
    public $controller;

    /**
     * @var string
     */
    public $action;

    /**
     * @var array
     */
    public $params = [];

    /**
     * @var \ManaPHP\Http\Controller
     */
    public $controllerInstance;

    /**
     * @var bool
     */
    public $isInvoking = false;
}
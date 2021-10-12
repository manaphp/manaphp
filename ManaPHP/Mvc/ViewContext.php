<?php

namespace ManaPHP\Mvc;

class ViewContext
{
    /**
     * @var int
     */
    public $max_age;

    /**
     * @var false|string|null
     */
    public $layout;

    /**
     * @var array
     */
    public $vars = [];

    /**
     * @var string
     */
    public $content;
}

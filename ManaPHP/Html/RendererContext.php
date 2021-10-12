<?php

namespace ManaPHP\Html;

use ManaPHP\Coroutine\Context\Inseparable;

class RendererContext implements Inseparable
{
    /**
     * @var array
     */
    public $sections = [];

    /**
     * @var array
     */
    public $stack = [];

    /**
     * @var array
     */
    public $templates = [];
}
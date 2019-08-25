<?php
namespace ManaPHP\Aop;

use Closure;

class Advice
{
    /**
     * @var Closure[]
     */
    public $before = [];

    /**
     * @var Closure
     */
    public $around = [];

    /**
     * @var Closure[]
     */
    public $after = [];
}
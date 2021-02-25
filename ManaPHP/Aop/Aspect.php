<?php

namespace ManaPHP\Aop;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Aop\ManagerInterface $aopManger
 */
abstract class Aspect extends Component
{
    abstract public function register();
}
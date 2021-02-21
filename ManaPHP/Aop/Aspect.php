<?php

namespace ManaPHP\Aop;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Aop\CutterInterface $aopCutter
 */
abstract class Aspect extends Component
{
    abstract public function register();
}
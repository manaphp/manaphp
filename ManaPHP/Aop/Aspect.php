<?php

namespace ManaPHP\Aop;

use ManaPHP\Component;

/**
 * Class Aspect
 *
 * @package ManaPHP\Aop
 * @property-read \ManaPHP\Aop\CutterInterface $aopCutter
 */
abstract class Aspect extends Component implements Unaspectable
{
    abstract public function register();
}
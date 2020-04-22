<?php

namespace ManaPHP;

use Closure;
use ManaPHP\Aop\JoinPoint;
use ManaPHP\Aop\Unaspectable;
use ReflectionClass;

class Aop implements AopInterface, Unaspectable
{
    /**
     * @var \ManaPHP\Aop\JoinPoint[][]
     */
    protected $_joinPoints;

    /**
     * @param string $class
     * @param string $method
     * @param string $signature
     *
     * @return \ManaPHP\Aop\Advice
     */
    public function pointcutMethod($class, $method, $signature = null)
    {
        if (!$joinPoint = $this->_joinPoints[$class][$method] ?? null) {
            /** @noinspection PhpUnusedLocalVariableInspection */
            $joinPoint = $this->_joinPoints[$class][$method] = new JoinPoint($class, $method, $signature);
        }

        return $joinPoint->advice;
    }

    /**
     * @param string|array $classes
     * @param Closure      $closure
     *
     * @return void
     */
    public function pointCutMethods($classes = '*', $closure = null)
    {
        if ($classes === '*') {
            $classes = get_declared_classes();
        }

        foreach ($classes as $class) {
            if ($class === 'ManaPHP\Loader' || strpos($class, 'Composer\\') === 0) {
                continue;
            }

            $rc = new ReflectionClass($class);
            if ($rc->isInternal() || $rc->implementsInterface(Unaspectable::class)) {
                continue;
            }

            if (preg_match('#^(.*)Context$#', $class, $match) === 1 && class_exists($match[1])) {
                continue;
            }

            foreach ($rc->getMethods() as $rm) {
                if ($rm->isAbstract()) {
                    continue;
                }
                $method = $rm->getName();
                if (strpos($method, '__') === 0 || strpos($method, '#') !== false || $rm->isStatic()) {
                    continue;
                }
                $this->pointcutMethod($class, $rm->getName())->addAfter($closure);
            }
        }
    }

    /**
     * @param Closure $closure
     */
    public function test($closure = null)
    {
        static $registered;

        $classes = [];
        foreach (get_declared_classes() as $class) {
            if (isset($registered[$class])) {
                continue;
            }
            $registered[$class] = 1;

            $classes[] = $class;
        }

        $this->pointCutMethods($classes, $closure);
    }
}
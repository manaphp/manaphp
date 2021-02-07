<?php

namespace ManaPHP\Aop;

use ReflectionClass;

class Cutter implements CutterInterface, Unaspectable
{
    /**
     * @var \ManaPHP\Aop\JoinPoint[][]
     */
    protected $joinPoints;

    /**
     * @param string $class
     * @param string $method
     * @param string $signature
     *
     * @return \ManaPHP\Aop\Advice
     */
    public function pointcutMethod($class, $method, $signature = null)
    {
        if (!$joinPoint = $this->joinPoints[$class][$method] ?? null) {
            /** @noinspection PhpUnusedLocalVariableInspection */
            $joinPoint = $this->joinPoints[$class][$method] = new JoinPoint($class, $method, $signature);
        }

        return $joinPoint->advice;
    }

    /**
     * @param string|array $classes
     * @param callable     $closure
     *
     * @return void
     */
    public function pointCutMethods($classes = '*', $closure = null)
    {
        if ($classes === '*') {
            $classes = get_declared_classes();
        }

        foreach ($classes as $class) {
            if ($class === 'ManaPHP\Loader' || str_starts_with($class, 'Composer\\')) {
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
                if (str_starts_with($method, '__') || str_contains($method, '#') || $rm->isStatic()) {
                    continue;
                }
                $this->pointcutMethod($class, $rm->getName())->addAfter($closure);
            }
        }
    }

    /**
     * @param callable $closure
     *
     * @return void
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
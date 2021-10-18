<?php

namespace ManaPHP\Di;

interface PropertyResolverInterface
{
    /**
     * @param string $class
     * @param string $property
     *
     * @return string
     */
    public function resolve($class, $property);
}
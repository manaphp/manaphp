<?php
namespace ManaPHP\Cli;

interface EnvironmentInterface
{
    /**
     * @param string $name
     * @param string $defaultValue
     *
     * @return string
     */
    public function get($name, $defaultValue = null);

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name);
}
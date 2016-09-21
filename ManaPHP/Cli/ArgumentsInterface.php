<?php
namespace ManaPHP\Cli;

interface ArgumentsInterface
{
    /**
     * @param string $name
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function get($name = null, $defaultValue = null);

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name);
}
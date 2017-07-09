<?php
namespace ManaPHP\Cli;

/**
 * Interface ManaPHP\Cli\ArgumentsInterface
 *
 * @package ManaPHP\Cli
 */
interface ArgumentsInterface
{
    /**
     * @param string|int $name
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
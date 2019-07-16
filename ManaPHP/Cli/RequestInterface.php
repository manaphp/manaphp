<?php
namespace ManaPHP\Cli;

/**
 * Interface ManaPHP\Cli\RequestInterface
 *
 * @package ManaPHP\Cli
 */
interface RequestInterface
{
    /**
     * @param array|string $arguments
     *
     * @return static
     */
    public function parse($arguments = null);

    /**
     * @param string|int $name
     * @param mixed      $default
     *
     * @return mixed
     */
    public function get($name = null, $default = null);

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * @param int $position
     *
     * @return string
     */
    public function getValue($position);

    /**
     * @return array
     */
    public function getValues();
}
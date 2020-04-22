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

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getServer($name = null, $default = '');

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasServer($name);

    /**
     * @return string
     */
    public function getRequestId();

    /**
     * @param string $request_id
     *
     * @return void
     */
    public function setRequestId($request_id = null);

    /**
     * @param object $instance
     * @param string $command
     *
     * @return void
     */
    public function completeShortNames($instance, $command);
}
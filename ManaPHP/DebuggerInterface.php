<?php
namespace ManaPHP;

/**
 * Interface ManaPHP\DebuggerInterface
 *
 * @package ManaPHP
 */
interface DebuggerInterface
{
    /**
     *
     * @return static
     */
    public function start();

    /**
     * @param mixed  $value
     * @param string $name
     *
     * @return static
     */
    public function var_dump($value, $name = null);

    /**
     * @param string $template
     *
     * @return string|array
     */
    public function output($template = 'Default');

    /**
     * @param string $template
     *
     * @return string
     */
    public function save($template = 'Default');
}
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
     * @param mixed      $defaultValue
     *
     * @return mixed
     */
    public function getOption($name = null, $defaultValue = null);

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasOption($name);

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
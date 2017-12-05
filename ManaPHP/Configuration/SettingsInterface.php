<?php
namespace ManaPHP\Configuration;

interface SettingsInterface extends \ArrayAccess
{
    /**
     * @param string $section
     * @param string $key
     * @param string $defaultValue
     *
     * @return string|array
     */
    public function get($section, $key = null, $defaultValue = '');

    /**
     * @param string       $section
     * @param string|array $key
     * @param string       $value
     *
     * @return void
     */
    public function set($section, $key, $value = null);

    /**
     * @param string $section
     * @param string $key
     *
     * @return bool
     */
    public function exists($section, $key = null);

    /**
     * @param string $section
     * @param string $key
     *
     * @return void
     */
    public function delete($section, $key);
}
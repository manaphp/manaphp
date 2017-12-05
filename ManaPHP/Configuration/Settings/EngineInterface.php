<?php
namespace ManaPHP\Configuration\Settings;

interface EngineInterface
{
    /**
     * @param string $section
     *
     * @return array
     */
    public function get($section);

    /**
     * @param string       $section
     * @param string|array $key
     * @param string       $value
     *
     * @return mixed
     */
    public function set($section, $key, $value = null);

    /**
     * @param string $section
     * @param string $key
     *
     * @return bool
     */
    public function exists($section, $key);

    /**
     * @param string $section
     * @param string $key
     *
     * @return void
     */
    public function delete($section, $key);
}
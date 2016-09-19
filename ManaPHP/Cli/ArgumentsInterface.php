<?php
namespace ManaPHP\Cli;

interface ArgumentsInterface
{
    const TYPE_NONE = 0;
    const TYPE_REQUIRE = 1;
    const TYPE_OPTIONAL = 2;

    /**
     * @param string $name
     * @param int    $type
     * @param string $description
     *
     * @return mixed
     */
    public function set($name, $type, $description = '');

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
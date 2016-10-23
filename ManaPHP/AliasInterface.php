<?php
namespace ManaPHP;

/**
 * Interface ManaPHP\AliasInterface
 *
 * @package ManaPHP
 */
interface AliasInterface
{
    /**
     * @param string $name
     * @param string $path
     *
     * @return string
     */
    public function set($name, $path);

    /**
     * @param string $name
     *
     * @return string|false
     */
    public function get($name);

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * @param string $path
     *
     * @return string
     */
    public function resolve($path);
}
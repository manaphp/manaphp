<?php

namespace ManaPHP;

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
     * @return array|string|null
     */
    public function get($name = null);

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

    /**
     * @param string $ns
     *
     * @return string
     */
    public function resolveNS($ns);
}
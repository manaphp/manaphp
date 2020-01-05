<?php
namespace ManaPHP;

interface DotenvInterface
{
    /**
     * @param string $file
     *
     * @return static
     */
    public function load($file = null);

    /**
     * @param array $lines
     *
     * @return array
     */
    public function parse($lines);

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed|array
     */
    public function get($key = null, $default = null);
}
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
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed|array
     */
    public function getEnv($name, $default = null);
}
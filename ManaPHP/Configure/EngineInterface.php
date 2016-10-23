<?php
namespace ManaPHP\Configure;

/**
 * Interface ManaPHP\Configure\EngineInterface
 *
 * @package ManaPHP\Configure
 */
interface EngineInterface
{
    /**
     * @param string $file
     *
     * @return mixed
     */
    public function load($file);
}
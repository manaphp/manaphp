<?php
namespace ManaPHP\Configure;

/**
 * Interface ManaPHP\Configure\EngineInterface
 *
 * @package configure
 */
interface EngineInterface
{
    /**
     * @param string $file
     *
     * @return array
     */
    public function load($file);
}
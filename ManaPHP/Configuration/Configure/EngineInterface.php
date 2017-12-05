<?php
namespace ManaPHP\Configuration\Configure;

/**
 * Interface ManaPHP\Configuration\Configure\EngineInterface
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
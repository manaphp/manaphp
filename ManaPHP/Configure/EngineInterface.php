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
     * @return mixed
     */
    public function load($file);
}
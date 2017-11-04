<?php

namespace ManaPHP;

/**
 * Interface ManaPHP\ConfigureInterface
 *
 * @package configure
 *
 * @property \ManaPHP\Cli\EnvironmentInterface                             $environment
 */
interface ConfigureInterface
{
    /**
     * @param string $file
     * @param string $env
     *
     * @return static
     */
    public function load($file, $env = null);
}
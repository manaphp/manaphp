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
     * @param string $mode
     *
     * @return static
     */
    public function load($file, $mode = null);
}
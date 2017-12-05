<?php

namespace ManaPHP\Configuration;

/**
 * Interface ManaPHP\Configuration\ConfigureInterface
 *
 * @package configure
 */
interface ConfigureInterface
{
    /**
     * @param string $file
     * @param string $env
     *
     * @return static
     */
    public function loadFile($file, $env = null);

    /**
     * @param array  $data
     * @param string $env
     * @return static
     * @throws \ManaPHP\Configuration\Configure\Exception
     */
    public function loadData($data, $env = null);
}
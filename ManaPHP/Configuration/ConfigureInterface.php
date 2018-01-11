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
     * @param string|array $files
     * @param string       $env
     *
     * @return static
     */
    public function loadFile($files, $env = null);

    /**
     * @param array  $data
     * @param string $env
     *
     * @return static
     * @throws \ManaPHP\Configuration\Configure\Exception
     */
    public function loadData($data, $env = null);
}
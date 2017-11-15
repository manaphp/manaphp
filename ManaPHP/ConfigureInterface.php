<?php

namespace ManaPHP;

/**
 * Interface ManaPHP\ConfigureInterface
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
     * @throws \ManaPHP\Configure\Exception
     */
    public function loadData($data, $env = null);
}
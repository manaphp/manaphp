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
     *
     * @return static
     */
    public function loadFile($files);

    /**
     * @param array  $data
     *
     * @return static
     * @throws \ManaPHP\Configuration\Configure\Exception
     */
    public function loadData($data);
}
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
     * @return static
     */
    public function load();

    /**
     * @param array $data
     *
     * @return static
     * @throws \ManaPHP\Configuration\Configure\Exception
     */
    public function loadData($data);
}
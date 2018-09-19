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
     *
     * @return static
     */
    public function load($file = '@config/app.php');

    /**
     * @param array $data
     *
     * @return static
     * @throws \ManaPHP\Configuration\Configure\Exception
     */
    public function loadData($data);
}
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
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getParam($name, $default = null);
}
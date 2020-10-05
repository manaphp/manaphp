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

    /**
     * @return static
     */
    public function registerAliases();

    /**
     * @return static
     */
    public function registerComponents();

    /**
     * @return static
     */
    public function registerAspects();

    /**
     * @return static
     */
    public function registerServices();

    /**
     * @return static
     */
    public function registerPlugins();

    /**
     * @return static
     */
    public function registerListeners();
}
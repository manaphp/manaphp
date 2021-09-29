<?php

namespace ManaPHP\Configuration;

interface ConfigureInterface
{
    /**
     * @param string $file
     *
     * @return static
     */
    public function load($file = '@config/app.php');

    /**
     * @return array
     */
    public function getConfig();

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
    public function registerCommands();

    /**
     * @return static
     */
    public function registerAspects();

    /**
     * @return static
     */
    public function registerPlugins();

    /**
     * @return static
     */
    public function registerListeners();
}
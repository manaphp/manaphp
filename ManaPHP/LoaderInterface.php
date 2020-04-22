<?php

namespace ManaPHP;

interface LoaderInterface
{
    /**
     * Register namespaces and their related directories
     *
     * <code>
     * $loader->registerNamespaces(array(
     *        ’Example\\Base’ => ’vendor/example/base/’,
     *        ’Example\\Adapter’ => ’vendor/example/adapter/’,
     *        ’Example’ => ’vendor/example/’
     *        ));
     * </code>
     *
     * @param array $namespaces
     * @param bool  $merge
     *
     * @return static
     */
    public function registerNamespaces($namespaces, $merge = true);

    /**
     * @return array
     */
    public function getRegisteredNamespaces();

    /**
     * Register classes and their locations
     *
     * @param array $classes
     * @param bool  $merge
     *
     * @return static
     */
    public function registerClasses($classes, $merge = true);

    /**
     * @param string|array $files
     *
     * @return static
     */
    public function registerFiles($files);
}
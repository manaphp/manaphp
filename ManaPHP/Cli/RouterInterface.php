<?php
namespace ManaPHP\Cli;

/**
 * Interface ManaPHP\Cli\RouterInterface
 *
 * @package router
 */
interface RouterInterface
{
    /**
     * @return string
     */
    public function getControllerName();

    /**
     * @return string
     */
    public function getActionName();

    /**
     * @param string $cmd
     *
     * @return bool
     */
    public function route($cmd);
}
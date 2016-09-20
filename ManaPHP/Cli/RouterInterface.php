<?php
namespace ManaPHP\Cli;

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
    public function handle($cmd);

    /**
     * @param string $alias
     * @param string $command
     *
     * @return static
     */
    public function setAlias($alias, $command);
}
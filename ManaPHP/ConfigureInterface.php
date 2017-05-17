<?php

namespace ManaPHP;

/**
 * Interface ManaPHP\ConfigureInterface
 *
 * @package configure
 *
 * @property \ConfManaPHP\Db\Adapter\Mysql|\ConfManaPHP\Db\Adapter\Mysql[] $db
 * @property \ConfManaPHP\Logger\Adapter\File                              $logger
 * @property array                                                         $modules
 * @property \ConfManaPHP\Redis|\ConfManaPHP\Redis[]                       $redis
 * @property \ConfManaPHP\Security\Crypt                                   $crypt
 * @property \ManaPHP\Cli\EnvironmentInterface                             $environment
 * @property \ManaPHP\Cli\ArgumentsInterface                               $arguments
 */
interface ConfigureInterface
{
    /**
     * @param string $type
     *
     * @return string
     */
    public function getSecretKey($type);

    /**
     * @return static
     */
    public function reset();

    /**
     * @param string $file
     * @param string $mode
     *
     * @return static
     * @throws \ManaPHP\Configure\Exception
     */
    public function load($file, $mode = null);
}
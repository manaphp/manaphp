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
 * @property \ConfManaPHP\Redis|\ConfManaPHP\Redis[]                       redis
 * @property \ConfManaPHP\Security\Crypt                                   crypt
 */
interface ConfigureInterface
{
    /**
     * @param string $type
     *
     * @return string
     */
    public function getSecretKey($type);
}
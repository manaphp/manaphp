<?php
namespace ManaPHP;

/**
 * Interface ManaPHP\ConfigureInterface
 *
 * @package configure
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
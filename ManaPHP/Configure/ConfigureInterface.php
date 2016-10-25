<?php
namespace ManaPHP\Configure;

/**
 * Interface ManaPHP\Configure\ConfigureInterface
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
<?php
namespace ManaPHP\Configure;

/**
 * Interface ManaPHP\Configure\ConfigureInterface
 *
 * @package ManaPHP\Configure
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
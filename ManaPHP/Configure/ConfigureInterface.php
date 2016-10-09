<?php
namespace ManaPHP\Configure;

interface ConfigureInterface
{
    /**
     * @param string $type
     *
     * @return string
     */
    public function getSecretKey($type);
}
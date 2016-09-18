<?php
namespace ManaPHP;

interface ApplicationInterface
{
    /**
     * @return void
     */
    public function registerServices();

    /**
     * @return void
     */
    public function main();

    /**
     * @return array
     */
    public function getModules();
}
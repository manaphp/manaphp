<?php
namespace ManaPHP;

/**
 * Interface ManaPHP\ApplicationInterface
 *
 * @package ManaPHP
 */
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
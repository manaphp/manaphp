<?php

namespace ManaPHP;

/**
 * Interface ManaPHP\ApplicationInterface
 *
 * @package application
 */
interface ApplicationInterface
{
    /**
     * @return void
     */
    public function registerConfigure();

    /**
     * @return void
     */
    public function main();

    /**
     * @param \Throwable $exception
     */
    public function handleException($exception);
}
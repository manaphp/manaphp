<?php
namespace ManaPHP;

interface ErrorHandlerInterface
{
    /**
     * @param \Exception|\ManaPHP\Exception $exception
     */
    public function handle($exception);
}
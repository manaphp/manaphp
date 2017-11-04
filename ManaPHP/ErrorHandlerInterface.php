<?php
namespace ManaPHP;

interface ErrorHandlerInterface
{
    /**
     * @param \Exception $exception
     */
    public function handleException($exception);
}
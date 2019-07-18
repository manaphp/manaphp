<?php
namespace ManaPHP;

interface ErrorHandlerInterface
{
    /**
     * @param \Throwable $exception
     */
    public function handle($exception);
}
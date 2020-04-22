<?php

namespace ManaPHP;

interface ErrorHandlerInterface
{
    /**
     * @param \Throwable $throwable
     */
    public function handle($throwable);
}
<?php
namespace ManaPHP\Mvc;

interface HandlerInterface
{
    /**
     * @return \ManaPHP\Http\ResponseInterface
     */
    public function handle();
}

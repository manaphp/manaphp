<?php
namespace Application\Api;

class Module extends \ManaPHP\Mvc\Module
{
    public function registerServices()
    {
        $this->csrfToken->disable();
    }
}
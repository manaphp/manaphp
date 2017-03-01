<?php
namespace Application\Api;

class Module extends \ManaPHP\Mvc\Module
{
    public function registerServices($di)
    {
        parent::registerServices($di);
        $this->csrfToken->disable();
    }
}
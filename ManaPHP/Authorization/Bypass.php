<?php
namespace ManaPHP\Authorization;

use ManaPHP\AuthorizationInterface;

class Bypass implements AuthorizationInterface
{
    public function authorize($dispatcher)
    {
        return true;
    }

    public function isAllowed($permission, $userId = null)
    {
        return true;
    }
}
<?php
namespace ManaPHP\Identity\Adapter;

use ManaPHP\Identity;

class Dummy extends Identity
{
    public function authenticate($silent = true)
    {
        return true;
    }
}
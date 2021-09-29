<?php

namespace ManaPHP\Service;

use ManaPHP\Helper\LocalFS;

class Provider extends \ManaPHP\Di\Provider
{
    public function __construct()
    {
        foreach (LocalFS::glob('@app/Services/?*Service.php') as $file) {
            $service = lcfirst(basename($file, '.php'));
            $this->definitions[$service] = 'App\Services\\' . ucfirst($service);
        }
    }
}
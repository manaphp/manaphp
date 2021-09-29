<?php

namespace ManaPHP\Cli\Commands;

use ManaPHP\Helper\LocalFS;

class Provider extends \ManaPHP\Di\Provider
{
    public function __construct()
    {
        foreach (LocalFS::glob('@app/Commands/?*Command.php') as $file) {
            $command = basename($file, '.php');
            $this->definitions[lcfirst($command)] = "App\Commands\\$command";
        }
    }
}
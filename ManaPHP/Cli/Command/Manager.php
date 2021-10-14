<?php

namespace ManaPHP\Cli\Command;

use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;

class Manager extends Component implements ManagerInterface
{
    /**
     * @var array
     */
    protected $commands = [];

    /**
     * @return array
     */
    public function getCommands()
    {
        if ($this->commands === []) {
            foreach (LocalFS::glob('@manaphp/Commands/?*Command.php') as $file) {
                $name = basename($file, 'Command.php');
                $this->commands[lcfirst($name)] = "ManaPHP\Commands\\{$name}Command";
            }

            foreach (LocalFS::glob('@app/Commands/?*Command.php') as $file) {
                $name = basename($file, 'Command.php');
                $this->commands[lcfirst($name)] = "App\Commands\\{$name}Command";
            }
        }

        return $this->commands;
    }
}
<?php
declare(strict_types=1);

namespace ManaPHP\Cli\Command;

use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;

class Manager extends Component implements ManagerInterface
{
    protected array $commands = [];

    /**
     * @return array
     */
    public function getCommands(): array
    {
        if ($this->commands === []) {
            $commands = [];

            foreach (LocalFS::glob('@manaphp/Commands/?*Command.php') as $file) {
                $name = basename($file, 'Command.php');
                $commands[lcfirst($name)] = "ManaPHP\Commands\\{$name}Command";
            }

            foreach (LocalFS::glob('@app/Commands/?*Command.php') as $file) {
                $name = basename($file, 'Command.php');
                $commands[lcfirst($name)] = "App\Commands\\{$name}Command";
            }

            ksort($commands);

            $this->commands = $commands;
        }

        return $this->commands;
    }
}
<?php
namespace Application\Cli;

class Application extends \ManaPHP\Cli\Application
{
    public function main()
    {
        $this->configure->loadFile('@apps/config.php', 'dev');
        $this->configure->bootstraps = [];

        parent::main();
    }
}
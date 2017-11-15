<?php
namespace Application;

use ManaPHP\Cli\Application;

class Cli extends Application
{
    public function main()
    {
        $this->configure->loadFile('@app/config.php', 'dev');
        $this->configure->bootstraps = [];

        parent::main();
    }
}
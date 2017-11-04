<?php
namespace Application;

use ManaPHP\Cli\Application;

class Cli extends Application
{
    public function main()
    {
        $this->configure->load('@app/config.php', 'dev');
        $this->configure->bootstraps = [];

        parent::main();
    }
}
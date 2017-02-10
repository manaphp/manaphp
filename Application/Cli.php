<?php
namespace Application;

use ManaPHP\Cli\Application;

class Cli extends Application
{
    public function main()
    {

        $this->registerServices();

        $this->debugger->start();

        exit($this->handle());
    }
}
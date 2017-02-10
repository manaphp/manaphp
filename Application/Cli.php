<?php
namespace Application;

use ManaPHP\Cli\Application;

class Cli extends Application
{
    public function main()
    {
        date_default_timezone_set('PRC');

        $this->registerServices();

        $this->debugger->start();

        exit($this->handle());
    }
}
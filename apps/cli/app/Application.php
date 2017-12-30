<?php
namespace App\Cli;

class Application extends \ManaPHP\Cli\Application
{
    public function main()
    {
        $this->alias->set('@cli', '@app/Controllers');
        $this->alias->set('@ns.cli', '@ns.app\\Controllers');

        parent::main();
    }
}
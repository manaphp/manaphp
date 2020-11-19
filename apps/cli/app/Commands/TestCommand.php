<?php

namespace App\Commands;

class TestCommand extends Command
{
    /**
     * @CliCommand demo for cli write
     */
    public function defaultAction()
    {
        var_dump(fnmatch('*er','SControllers'));
    }
}

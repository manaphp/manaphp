<?php
namespace App\Controllers;

use ManaPHP\Cli\Controller;

class TestController extends Controller
{
    /**
     * @CliCommand demo for cli write
     */
    public function defaultCommand()
    {
        var_dump($this->environment->get('PATH'));
        var_dump(get_included_files());
    }
}
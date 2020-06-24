<?php

namespace App\Cli\Controllers;

use ManaPHP\Cli\Controller;

/**
 * Class TestController
 *
 * @package App\Cli\Controllers
 */
class TestController extends Controller
{
    public function defaultCommand()
    {
        $this->console->writeLn(date('Y-m-d H:i:s'));
    }
}
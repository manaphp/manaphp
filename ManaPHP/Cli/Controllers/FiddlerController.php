<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;

/**
 * Class FiddlerController
 * @package ManaPHP\Cli\Controllers
 * @property-read \ManaPHP\Plugins\FiddlerPlugin $fiddlerPlugin
 */
class FiddlerController extends Controller
{
    public function defaultCommand()
    {
        $this->fiddlerPlugin->subscribe();
    }
}
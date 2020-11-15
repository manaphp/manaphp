<?php

namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;

/**
 * Class DotenvController
 *
 * @package App\Cli\Controllers
 *
 * @property-read \ManaPHP\Configuration\DotenvInterface $dotenv
 */
class DotenvController extends Controller
{
    public function defaultCommand()
    {
        foreach ($this->dotenv->get() as $k => $v) {
            echo sprintf('%s=%s', $k, $v), PHP_EOL;
        }
    }
}

<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;

/**
 * Class ManaPHP\Cli\Controllers\SessionController
 *
 * @package  ManaPHP\Cli\Controllers
 * @property \ManaPHP\Http\SessionInterface $session
 */
class  SessionController extends Controller
{
    public function defaultCommand()
    {
        $this->session->clean();
    }
}
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
    /**
     * clean expired sessions
     */
    public function cleanCommand()
    {
        $this->session->clean();
    }
}
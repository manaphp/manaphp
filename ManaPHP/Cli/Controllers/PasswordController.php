<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;

/**
 * Class PasswordController
 * @package ManaPHP\Cli\Controllers
 * @property \ManaPHP\Authentication\PasswordInterface $password
 */
class PasswordController extends Controller
{
    /**
     * calc password
     * @param string $salt
     * @param string $password
     */
    public function defaultCommand($salt = '', $password = '')
    {
        if ($salt === '') {
            $salt = $this->password->salt();
        }

        $this->console->writeLn('salt: ' . $salt);
        $this->console->writeLn('hash: '. $this->password->hash($password, $salt));
    }
}
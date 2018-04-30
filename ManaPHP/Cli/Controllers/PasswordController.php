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
     *  calculate password hash
     *
     * @param string $salt
     * @param string $password
     */
    public function hashCommand($salt = '', $password = '')
    {
        if ($salt === '') {
            $salt = $this->password->salt();
        } elseif (is_numeric($salt) && $salt < 100) {
            $salt = $this->password->salt($salt);
        }

        $this->console->writeLn('salt: ' . $salt);
        $this->console->writeLn('hash: ' . $this->password->hash($password, $salt));
    }
}
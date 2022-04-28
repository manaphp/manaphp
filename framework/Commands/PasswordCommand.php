<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Helper\Str;

class PasswordCommand extends Command
{
    /**
     * generate a new password
     *
     * @param int    $length
     * @param string $password
     * @param int    $base
     * @param int    $cost
     *
     * @return void
     */
    public function generateAction(int $length = 32, string $password = '', int $base = 62, int $cost = 0): void
    {
        if ($password === '') {
            $password = Str::random($length, $base);
        }

        $this->console->writeLn('password: ' . $password);

        if ($cost) {
            $this->console->writeLn('hash: ' . password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]));
        } else {
            for ($i = 7; $i < 14; $i++) {
                $start = microtime(true);
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => $i]);
                $elapsed = microtime(true) - $start;
                $this->console->writeLn(sprintf('%.3f: %s', $elapsed, $hash));
            }
        }
    }


    /**
     * generate password with every kind of cost
     *
     * @return void
     */
    public function costAction(): void
    {
        for ($i = 7; $i < 14; $i++) {
            $start = microtime(true);
            password_hash('password', PASSWORD_BCRYPT, ['cost' => $i]);
            $this->console->writeLn(sprintf('%2d: %.3f', $i, microtime(true) - $start));
        }
    }
}
<?php

namespace ManaPHP\Debugging\DebuggerPlugin;

class Command extends \ManaPHP\Cli\Command
{
    /**
     * monitor generated urls
     *
     * @param string $id
     * @param string $path
     * @param string $ip
     */
    public function watchAction($id = '', $path = '', $ip = '')
    {
        if ($id === '') {
            $id = $this->configure->id;
        }

        $key = "__debuggerPlugin:$id:*";
        $this->console->writeLn('subscribe to ' . $key);

        $this->redisCache->psubscribe(
            [$key], function ($redis, $pattern, $chan, $msg) use ($path, $ip) {
            list($_, $__, $_ip, $_path) = explode(':', $chan);
            if ($path !== '' && !str_starts_with($_path, $path)) {
                return;
            }

            if ($ip !== '' && $ip !== $_ip) {
                if (str_contains($ip, '.')) {
                    return;
                } elseif (!str_ends_with($_ip, ".$ip")) {
                    return;
                }
            }

            $this->console->writeLn(['[%s][%s]: %s', $_ip, $_path, $msg]);
        }
        );
    }
}
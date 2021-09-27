<?php

namespace ManaPHP\Debugging\DebuggerPlugin;

/**
 * @property-read \Redis $redisCache
 */
class Command extends \ManaPHP\Cli\Command
{
    /**
     * monitor generated urls
     *
     * @param string $id
     * @param string $path
     * @param string $ip
     *
     * @return void
     */
    public function watchAction($id = APP_ID, $path = '', $ip = '')
    {
        $key = "__debuggerPlugin:$id:*";
        $this->console->writeLn('subscribe to ' . $key);

        $this->redisCache->psubscribe(
            [$key], function ($redis, $pattern, $channel, $msg) use ($path, $ip) {
            list(, , $_ip, $_path) = explode(':', $channel);
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
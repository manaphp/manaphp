<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Redis\RedisCacheInterface;

class DebuggerCommand extends Command
{
    #[Autowired] protected RedisCacheInterface $redisCache;

    #[Config] protected string $app_id;

    /**
     * monitor generated urls
     *
     * @param ?string $app_id
     * @param string  $path
     * @param string  $ip
     *
     * @return void
     */
    public function watchAction(?string $app_id = null, string $path = '', string $ip = ''): void
    {
        $app_id = $app_id ?? $this->app_id;
        $key = "__debugger:$app_id:*";
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

            $this->console->writeLn(sprintf('[%s][%s]: %s', $_ip, $_path, $msg));
        }
        );
    }
}
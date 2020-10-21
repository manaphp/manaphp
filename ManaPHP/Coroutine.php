<?php

namespace ManaPHP;

use Swoole\Coroutine as SwooleCoroutine;

class Coroutine
{
    public static function getBacktrace($options, $limit = 0)
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            return SwooleCoroutine::getBackTrace(0, $options, $limit);
        } else {
            return debug_backtrace($options, $limit);
        }
    }
}
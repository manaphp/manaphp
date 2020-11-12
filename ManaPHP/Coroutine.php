<?php

namespace ManaPHP;

use Swoole\Coroutine as SwooleCoroutine;

class Coroutine
{
    public static function getBacktrace($options, $limit = 0)
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            $traces = SwooleCoroutine::getBackTrace(0, $options, $limit + 1);
            array_shift($traces);
            return $traces;
        } else {
            return debug_backtrace($options, $limit);
        }
    }
}
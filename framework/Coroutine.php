<?php
declare(strict_types=1);

namespace ManaPHP;

use Swoole\Coroutine as SwooleCoroutine;

class Coroutine
{
    public static function getBacktrace(int $options, int $limit = 0): array
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            $traces = SwooleCoroutine::getBackTrace(0, $options, $limit > 0 ? $limit + 1 : 0);
            array_shift($traces);
        } else {
            $traces = debug_backtrace($options, $limit);
        }
        array_shift($traces);

        return $traces;
    }

    public static function create(callable $func, ...$params): int|bool
    {
        return SwooleCoroutine::create($func, ...$params);
    }
}
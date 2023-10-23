<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Http\Metrics\AbstractCollector;
use Swoole\Coroutine;

class CoroutineOptionsCollector extends AbstractCollector
{
    public function export(): string
    {
        $str = '';
        foreach (Coroutine::getOptions() as $name => $value) {
            if (is_int($value)) {
                $str .= $this->formatter->gauge('swoole_coroutine_options_' . $name, $value);
            } else {
                $str .= $this->formatter->gauge('swoole_coroutine_options_' . $name, 1, ['value' => $name]);
            }
        }
        return $str;
    }
}
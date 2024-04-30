<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Metrics\CollectorInterface;
use ManaPHP\Http\Metrics\FormatterInterface;
use Swoole\Coroutine;
use function is_int;

class CoroutineOptionsCollector implements CollectorInterface
{
    #[Autowired] protected FormatterInterface $formatter;

    public function export(mixed $data): string
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
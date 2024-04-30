<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Metrics\CollectorInterface;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Swoole\WorkersInterface;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

class ServerSettingsCollector implements CollectorInterface
{
    #[Autowired] protected FormatterInterface $formatter;
    #[Autowired] protected WorkersInterface $workers;

    public function export(mixed $data): string
    {
        $str = '';
        foreach ($this->workers->getServer()->setting as $name => $value) {
            if (is_int($value) || is_float($value)) {
                $str .= $this->formatter->gauge('swoole_server_settings_' . $name, $value);
            } elseif (is_bool($value)) {
                $str .= $this->formatter->gauge('swoole_server_settings_' . $name, (int)$value);
            } elseif (is_string($value)) {
                $str .= $this->formatter->gauge('swoole_server_settings_' . $name, 1, ['value' => $value]);
            }
        }

        return $str;
    }
}
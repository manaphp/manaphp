<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics;

use ManaPHP\Di\Attribute\Config;

class Formatter implements FormatterInterface
{
    #[Config] protected string $app_id;

    protected string $prefix = '';

    /**
     * @param array $labels
     *
     * @return string
     */
    public function labels($labels)
    {
        if (isset($labels['app'])) {
            if ($labels['app'] === '') {
                unset($labels['app']);
            }
        } elseif ($this->app_id !== '') {
            $labels = ['app' => $this->app_id] + $labels;
        }

        if ($labels) {
            $str_labels = [];
            foreach ($labels as $name => $value) {
                $str_labels[] = "{$name}=\"{$value}\"";
            }
            return '{' . implode(',', $str_labels) . '}';
        }
        return '';
    }

    public function gauge(string $name, int|array $value, array $labels = [], array $levels = []): string
    {
        return "# TYPE $name gauge\n" . $this->number($name, $value, $labels, $levels);
    }

    public function number(string $name, int|array $value, array $labels = [], array $levels = []): string
    {
        if ($levels === []) {
            return $name . $this->labels($labels) . " $value\n";
        } else {
            $str = '';
            $level = array_shift($levels);
            foreach ($value as $key => $val) {
                $str .= $this->number($name, $val, $labels + [$level => $key], $levels);
            }
        }
        return $str;
    }

    public function counter(string $name, int|array $value, array $labels = [], array $levels = []): string
    {
        return "# TYPE $name counter\n" . $this->number($name, $value, $labels, $levels);
    }

    public function histogram(string $name, Histogram $histogram, array $labels): string
    {
        $name = $this->prefix . $name;
        $str = "# TYPE $name histogram\n";

        foreach ($histogram->buckets as $le_name => $le_value) {
            $str .= $name . '_bucket' . $this->labels($labels + ['le' => $le_name]) . " $le_value\n";
        }
        $str .= $name . '_bucket' . $this->labels($labels + ['le' => '+Inf']) . " $histogram->count\n";

        $str .= $name . '_sum' . $this->labels($labels) . " $histogram->sum\n";
        $str .= $name . '_count' . $this->labels($labels) . " $histogram->count\n";

        return $str;
    }
}
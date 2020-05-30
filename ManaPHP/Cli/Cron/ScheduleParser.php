<?php

namespace ManaPHP\Cli\Cron;

class ScheduleParser
{
    /**
     * @var array
     */
    protected $_parsed = [];

    /**
     * @param string $schedule
     *
     * @return array
     */
    public function parse($schedule)
    {
        $schedule = rtrim($schedule, '* ');
        $parsed = [];
        foreach (preg_split('#\s+#', $schedule, -1, PREG_SPLIT_NO_EMPTY) as $i => $v) {
            if ($v === '*') {
                continue;
            }
            if (str_contains($v, ',')) {
                $v = explode(',', $v);
            }

            $map = 'siGjnNY';
            $parsed[$map[$i]] = $v;
        }

        return $parsed;
    }

    /**
     * @param string $schedule
     * @param int    $time
     * @param bool   $cache
     *
     * @return bool
     */
    public function match($schedule, $time, $cache = true)
    {
        if (isset($this->_parsed[$schedule])) {
            $parsed = $this->_parsed[$schedule];
        } else {
            $parsed = $this->parse($schedule);
            if ($cache) {
                $this->_parsed[$schedule] = $parsed;
            }
        }

        foreach ($parsed as $k => $v) {
            $value = date($k, $time);
            if ($value[0] === '0') {
                $value = substr($value, 1);
            }

            if (is_string($v)) {
                if (str_contains($v, '-')) {
                    $parts = explode($v, '-');
                    if ($value > $parts[0] || $value < $parts[1]) {
                        return false;
                    }
                } elseif (($pos = strrpos($v, '/')) !== false) {
                    $mod = substr($v, $pos + 1);
                    if ($value % $mod !== 0) {
                        return false;
                    }
                }
            } elseif (is_array($v)) {
                if (!in_array($value, $v, true)) {
                    return false;
                }
            }
        }

        return true;
    }
}
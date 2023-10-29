<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use DateTime;
use DateTimeZone;
use ManaPHP\Cli\Command;
use ManaPHP\Cli\OptionsInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\ClientInterface;

class DateCommand extends Command
{
    #[Autowired] protected ClientInterface $httpClient;
    #[Autowired] protected OptionsInterface $options;

    /**
     * @param string $url
     * @param bool   $onlyOnce
     *
     * @return ?int
     * @noinspection PhpUnusedLocalVariableInspection
     */
    protected function getRemoteTimestamp(string $url, bool $onlyOnce = false): ?int
    {
        if (!str_contains($url, '://')) {
            $url = 'https://' . $url;
        }

        $prev_timestamp = 0;
        do {
            try {
                $timestamp = strtotime($this->httpClient->head($url)->getHeaders()['Date']);
            } catch (\Exception $exception) {
                return null;
            }

            if ($prev_timestamp !== 0 && $prev_timestamp !== $timestamp) {
                break;
            }

            $prev_timestamp = $timestamp;
        } while (!$onlyOnce);

        return $timestamp;
    }

    /**
     * sync system time with http server time clock
     *
     * @param string $url the time original
     *
     * @return  int
     */
    public function syncAction(string $url = 'https://www.baidu.com'): int
    {
        $timestamp = $this->getRemoteTimestamp($url);
        if ($timestamp === null) {
            return $this->console->error('fetch remote timestamp failed: `{url}`', ['url' => $url]);
        } else {
            $this->updateDate($timestamp);
            $this->console->writeLn(date('Y-m-d H:i:s'));
            return 0;
        }
    }

    /**
     * show remote time
     *
     * @param string $url the time original
     *
     * @return int
     */
    public function remoteAction(string $url = 'https://www.baidu.com'): int
    {
        $timestamp = $this->getRemoteTimestamp($url);
        if ($timestamp === null) {
            return $this->console->error(sprintf('fetch remote timestamp failed: `%s`', $url));
        } else {
            $this->console->writeLn(date('Y-m-d H:i:s', $timestamp));
            return 0;
        }
    }

    /**
     * show local and remote diff
     *
     * @param string $url the time original
     *
     * @return int
     */
    public function diffAction(string $url = 'https://www.baidu.com'): int
    {
        $remote_ts = $this->getRemoteTimestamp($url);
        $local_ts = time();
        if ($remote_ts === null) {
            return $this->console->error(sprintf('fetch remote timestamp failed: `%s`', $url));
        } else {
            $this->console->writeLn(' local: ' . date('Y-m-d H:i:s', $local_ts));
            $this->console->writeLn('remote: ' . date('Y-m-d H:i:s', $remote_ts));
            $this->console->writeLn('  diff: ' . ($local_ts - $remote_ts));
            return 0;
        }
    }

    /**
     * set the system time
     *
     * @param string $date
     * @param string $time
     *
     * @return int
     */
    public function setAction(string $date = '', string $time = ''): int
    {
        $arguments = explode(' ', $this->options->get(''));
        if (\count($arguments) === 1) {
            $argument = $arguments[0];
            if ($argument[0] === 't') {
                $date = '';
                $time = substr($argument, 1);
            } else {
                $str = trim(strtr($argument, 'Tt', '  '));
                if (str_contains($str, ' ')) {
                    list($date, $time) = explode(' ', $str);
                } else {
                    if (str_contains($str, ':')) {
                        $date = '';
                        $time = $str;
                    } else {
                        $date = $str;
                        $time = '';
                    }
                }
            }
        }

        $date = $date ? strtr($date, '/', '-') : date('Y-m-d');
        $date = trim(trim($date), '-');

        switch (substr_count($date, '-')) {
            case 0:
                $date = date('Y-m-') . $date;
                break;
            case 1:
                $date = date('Y-') . $date;
                break;
        }

        $parts = explode('-', $date);

        $year = substr(date('Y'), 0, 4 - \strlen($parts[0])) . $parts[0];
        $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
        $day = str_pad($parts[2], 2, '0', STR_PAD_LEFT);

        $date = $year . '-' . $month . '-' . $day;

        $time = $time ? trim($time) : date('H:i:s');
        if ($time[0] === ':') {
            $time = date('H') . $time;
        }
        switch (substr_count($time, ':')) {
            case 0:
                $time .= date(':i:s');
                break;
            case 1:
                $time .= date(':s');
                break;
        }
        $parts = explode(':', $time);

        $hour = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
        $minute = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
        $second = str_pad($parts[2], 2, '0', STR_PAD_LEFT);

        $time = $hour . ':' . $minute . ':' . $second;

        $str = $date . ' ' . $time;
        $timestamp = strtotime($str);
        if ($timestamp === false || preg_match('#^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$#', $str) !== 1) {
            return $this->console->error(sprintf('`%s` time format is invalid', $str));
        } else {
            $this->updateDate($timestamp);
            $this->console->writeLn(date('Y-m-d H:i:s'));

            return 0;
        }
    }

    /**
     * @param int $timestamp
     *
     * @return void
     */
    protected function updateDate(int $timestamp): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            system('date ' . date('Y-m-d', $timestamp));
            system('time ' . date('H:i:s', $timestamp));
        } elseif (PHP_OS === 'Darwin') {
            $dt = (new DateTime())->setTimestamp($timestamp)
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('mdHiy');
            system('date -u ' . $dt);
        } else {
            system('date --set "' . date('Y-m-d H:i:s', $timestamp) . '"');
        }
    }
}
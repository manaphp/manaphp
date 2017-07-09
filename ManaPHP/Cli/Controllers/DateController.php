<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;

class DateController extends Controller
{
    /**
     * @CliCommand sync system time with http server time clock
     * @CliParam   --url the time original
     */
    public function syncCommand()
    {
        $url = $this->arguments->get('url', 'http://www.baidu.com');

        if (strpos($url, '://') === false) {
            $url = 'http://' . $url;
        }

        $prev_timestamp = 0;
        do {
            if ($this->httpClient->head($url) !== 200) {
                return $this->console->writeLn('communication with `:url` failed', ['url' => $url]);
            }
            $headers = $this->httpClient->getResponseHeaders();
            $timestamp = strtotime($headers['Date']);
            if ($prev_timestamp !== 0 && $prev_timestamp !== $timestamp) {
                break;
            }

            $prev_timestamp = $timestamp;
        } while (true);

        $this->_updateDate($timestamp);

        $this->console->write(date('Y-m-d H:i:s'));

        return 0;
    }

    /**
     * @CliCommand set the system time
     * @CliParam   --time:-t time
     * @CliParam   --date:-d date
     * @return int
     */
    public function setCommand()
    {
        $arguments = $this->arguments->get();
        if (count($arguments) === 1) {
            if (preg_match('#^\d+$#', $arguments[0]) === 1) {
                $date = date('ym') . str_pad($arguments[0], 8, '0');
            } else {
                $date = $arguments[0];
            }
        } else {
            $date = str_replace('-', '', $this->arguments->get('date:d', date('ymd'))) . str_replace(':', '', $this->arguments->get('time:t', date('His')));
        }

        if (strpos($date, ':') !== false) {
            $timestamp = strtotime($date);
        } else {
            list($year, $month, $day, $hour, $minute, $second) = str_split(str_pad($date, 12, '0'), 2);
            $timestamp = strtotime("20$year-$month-$day $hour:$minute:$second");
        }
        if ($timestamp === false) {
            $this->console->error('time format is invalid.');
            return 1;
        }

        $this->_updateDate($timestamp);

        $this->console->writeLn(date('Y-m-d H:i:s'));

        return 0;
    }

    protected function _updateDate($timestamp)
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            system('date ' . date('Y-m-d', $timestamp));
            system('time ' . date('H:i:s', $timestamp));
        } else {
            system('date --set "' . date('Y-m-d H:i:s', $timestamp) . '"');
        }
    }
}
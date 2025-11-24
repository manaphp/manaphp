<?php

declare(strict_types=1);

namespace ManaPHP\Logging\Appender;

use ManaPHP\Alias\Path;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Logging\AppenderInterface;
use ManaPHP\Logging\Log;
use function dirname;
use function error_get_last;
use function file_put_contents;
use function is_dir;
use function is_file;
use function mkdir;
use function preg_match_all;
use function preg_replace;
use function strtr;
use function trigger_error;

class FileAppender implements AppenderInterface
{
    #[Autowired] protected string $file = '@runtime/logger/{app_id}.log';
    #[Autowired] protected string $line_format = '[:time][:level][:category][:location] :message';

    #[Config] protected string $app_id;

    protected function format(Log $log): string
    {
        $replaced = [];

        preg_match_all('#:(\w+)#', $this->line_format, $matches);
        foreach ($matches[1] as $key) {
            if ($key === 'message') {
                if ($log->category === 'exception') {
                    $replaced[':message'] = '';
                    $message = preg_replace('#[\\r\\n]+#', '\0' . strtr($this->line_format, $replaced), $log->message);
                    $replaced[':message'] = $message . PHP_EOL;
                } else {
                    $replaced[':message'] = $log->message . PHP_EOL;
                }
            } else {
                $replaced[":$key"] = $log->$key ?? '-';
            }
        }

        return strtr($this->line_format, $replaced);
    }

    /**
     * @param string $str
     *
     * @return void
     */
    protected function write(string $str): void
    {
        $file = Path::resolve($this->file, ['app_id' => $this->app_id]);
        if (!is_file($file)) {
            $dir = dirname($file);
            if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
                trigger_error("Unable to create $dir directory: " . error_get_last()['message'], E_USER_WARNING);
            }
        }

        //The flag of LOCK_EX fights with SWOOLE COROUTINE
        if (file_put_contents($file, $str, FILE_APPEND) === false) {
            trigger_error('Write log to file failed: ' . $file, E_USER_WARNING);
        }
    }

    public function append(Log $log): void
    {
        $this->write($this->format($log));
    }
}

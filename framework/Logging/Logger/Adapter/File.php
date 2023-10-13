<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\AliasInterface;
use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Logging\AbstractLogger;
use ManaPHP\Logging\Logger\Log;

class File extends AbstractLogger
{
    #[Autowired] protected ConfigInterface $config;
    #[Autowired] protected AliasInterface $alias;

    #[Autowired] protected string $file = '@runtime/logger/{id}.log';
    #[Autowired] protected string $line_format = '[:time][:level][:category][:location] :message';

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
        $file = $this->alias->resolve(strtr($this->file, ['{id}' => $this->config->get('id')]));
        if (!is_file($file)) {
            $dir = dirname($file);
            if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
                trigger_error("Unable to create $dir directory: " . error_get_last()['message'], E_USER_WARNING);
            }
        }

        //LOCK_EX flag fight with SWOOLE COROUTINE
        if (file_put_contents($file, $str, FILE_APPEND) === false) {
            trigger_error('Write log to file failed: ' . $file, E_USER_WARNING);
        }
    }

    public function append(Log $log): void
    {
        $this->write($this->format($log));
    }
}
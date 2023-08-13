<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\AliasInterface;
use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Logging\AbstractLogger;
use ManaPHP\Logging\Level;
use ManaPHP\Logging\Logger\Log;

class File extends AbstractLogger
{
    #[Inject]
    protected ConfigInterface $config;
    #[Inject]
    protected AliasInterface $alias;

    protected string $file;
    protected string $format;

    public function __construct(string $file = '@runtime/logger/{id}.log',
        string $format = '[:date][:client_ip][:request_id16][:level][:category][:location] :message',
        string $level = Level::DEBUG, ?string $hostname = null
    ) {
        parent::__construct($level, $hostname);

        $this->file = strtr($file, ['{id}' => $this->config->get("id")]);
        $this->format = $format;
    }

    protected function format(Log $log): string
    {
        $replaced = [];

        $ms = sprintf('.%03d', ($log->timestamp - (int)$log->timestamp) * 1000);
        $replaced[':date'] = date('Y-m-d\TH:i:s', (int)$log->timestamp) . $ms;
        $replaced[':client_ip'] = $log->client_ip ?: '-';
        $replaced[':request_id'] = $log->request_id ?: '-';
        $replaced[':request_id16'] = $log->request_id ? substr($log->request_id, 0, 16) : '-';
        $replaced[':category'] = $log->category;
        $replaced[':location'] = "$log->file:$log->line";
        $replaced[':level'] = strtoupper($log->level);
        if ($log->category === 'exception') {
            $replaced[':message'] = '';
            $message = preg_replace('#[\\r\\n]+#', '\0' . strtr($this->format, $replaced), $log->message);
            $replaced[':message'] = $message . PHP_EOL;
        } else {
            $replaced[':message'] = $log->message . PHP_EOL;
        }

        return strtr($this->format, $replaced);
    }

    /**
     * @param string $str
     *
     * @return void
     */
    protected function write(string $str): void
    {
        $file = $this->alias->resolve($this->file);
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
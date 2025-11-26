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

        // Extract all placeholder keys from the line format (e.g., :time, :level, :message)
        preg_match_all('#:(\w+)#', $this->line_format, $matches);
        foreach ($matches[1] as $key) {
            if ($key === 'message') {
                // Special handling for exception messages: split multi-line messages
                // and prefix each line with the formatted log prefix (time, level, etc.)
                if ($log->category === 'exception') {
                    // First, replace all placeholders except message to get the prefix
                    $replaced[':message'] = '';
                    $prefix = strtr($this->line_format, $replaced);
                    // Replace newlines with the prefix, so each exception line has the log prefix
                    // \0 in replacement means the matched newline character is preserved
                    $message = preg_replace('#[\\r\\n]+#', '\0' . $prefix, $log->message);
                    $replaced[':message'] = $message . PHP_EOL;
                } else {
                    // Regular messages: just append the message with newline
                    $replaced[':message'] = $log->message . PHP_EOL;
                }
            } else {
                // Replace other placeholders with log properties or '-' if not available
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
        // Create directory if file doesn't exist yet
        if (!is_file($file)) {
            $dir = dirname($file);
            // Check if directory exists, create it if not, and verify creation succeeded
            // The double is_dir check handles race conditions where directory might be created
            // by another process between mkdir and the check
            if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
                $error = error_get_last()['message'] ?? 'Unknown error';
                trigger_error("Unable to create $dir directory: $error", E_USER_WARNING);
            }
        }

        // Note: LOCK_EX flag is not used because it conflicts with Swoole coroutines
        // Swoole's coroutine file operations don't work well with file locking
        if (file_put_contents($file, $str, FILE_APPEND) === false) {
            trigger_error('Write log to file failed: ' . $file, E_USER_WARNING);
        }
    }

    public function append(Log $log): void
    {
        $this->write($this->format($log));
    }
}

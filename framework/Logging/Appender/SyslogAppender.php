<?php

declare(strict_types=1);

namespace ManaPHP\Logging\Appender;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Logging\AppenderInterface;
use ManaPHP\Logging\Log;
use function date;
use function parse_url;
use function preg_match_all;
use function preg_split;
use function socket_close;
use function socket_create;
use function socket_sendto;
use function strlen;
use function strtr;

/** @noinspection SpellCheckingInspection */
//#/etc/rsyslog.d/99-app.conf
//$template myformat,"%msg%\n"
//$ActionFileDefaultTemplate myformat
//
//$template myTemplate,"/var/log/test/%PROGRAMNAME%.log"
//user.* ?myTemplate

class SyslogAppender implements AppenderInterface
{
    #[Autowired] protected string $uri;
    #[Autowired] protected int $facility = 1;
    #[Autowired] protected string $line_format = '[:date][:client_ip][:request_id16][:level][:category][:location] :message';

    #[Config] protected string $app_id;

    protected string $scheme;
    protected string $host;
    protected int $port;

    protected mixed $socket;

    /** @noinspection PhpTypedPropertyMightBeUninitializedInspection */
    public function __construct()
    {
        $parts = parse_url($this->uri);
        if ($parts === false || !isset($parts['host'])) {
            throw new NotSupportedException('Invalid syslog URI: "{uri}".', ['uri' => $this->uri]);
        }
        $this->host = $parts['host'];
        $this->scheme = $parts['scheme'] ?? 'udp';
        $this->port = $parts['port'] ? (int)$parts['port'] : 514;

        if ($this->scheme !== 'udp') {
            throw new NotSupportedException('Only UDP protocol is supported for remote logging, but "{scheme}" was specified.', ['scheme' => $this->scheme]);
        }

        $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($socket === false) {
            throw new NotSupportedException('Failed to create UDP socket for syslog: {error}.', ['error' => socket_strerror(socket_last_error())]);
        }
        $this->socket = $socket;
    }

    public function __destruct()
    {
        if ($this->socket !== null) {
            socket_close($this->socket);
        }
    }

    public function append(Log $log): void
    {
        // Map log levels to syslog severity constants
        // RFC 3164 defines severity levels: 0=emergency, 1=alert, 2=critical, 3=error, 4=warning, 5=notice, 6=info, 7=debug
        $severity = ['fatal' => LOG_CRIT,
                     'error' => LOG_ERR,
                     'warn'  => LOG_WARNING,
                     'info'  => LOG_INFO,
                     'debug' => LOG_DEBUG,
                    ][$log->level] ?? LOG_INFO;

        $host = $this->host;
        $port = $this->port;
        $tag = $this->app_id;

        // Calculate priority: facility * 8 + severity
        // Facility is typically 1 (user-level messages), range 0-23
        // Severity range is 0-7, so priority range is 0-191
        $priority = $this->facility * 8 + $severity;
        // Format timestamp in RFC 3164 format: "Mmm dd HH:mm:ss" (e.g., "Jan 15 14:30:00")
        $timestamp = date('M d H:i:s', (int)$log->timestamp);

        $replaced = [];

        // Extract all placeholder keys from the line format
        preg_match_all('#:(\w+)#', $this->line_format, $matches);
        foreach ($matches[1] as $key) {
            // Prepare replacements for all placeholders except message
            // Message will be handled separately for exception multi-line handling
            if ($key !== 'message') {
                $replaced[":$key"] = $log->$key ?? '-';
            }
        }

        // Special handling for exception messages: send each line as a separate syslog packet
        // This ensures stack traces are properly formatted in syslog
        if ($log->category === 'exception') {
            foreach (preg_split('#[\\r\\n]+#', $log->message) as $line) {
                $replaced[':message'] = $line;
                $content = strtr($this->line_format, $replaced);

                // RFC 3164 syslog format: <PRI>TIMESTAMP HOST TAG:CONTENT
                // PRI is the priority value in angle brackets
                // TAG is the program identifier (app_id)
                $packet = "<$priority>$timestamp $log->hostname $tag:$content";
                socket_sendto($this->socket, $packet, strlen($packet), 0, $host, $port);
            }
        } else {
            // Regular messages: send as a single syslog packet
            $replaced[':message'] = $log->message;
            $content = strtr($this->line_format, $replaced);

            // RFC 3164 syslog format: <PRI>TIMESTAMP HOST TAG:CONTENT
            $packet = "<$priority>$timestamp $log->hostname $tag:$content";
            socket_sendto($this->socket, $packet, strlen($packet), 0, $host, $port);
        }
    }
}

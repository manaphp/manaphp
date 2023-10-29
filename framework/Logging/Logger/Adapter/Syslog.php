<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Logging\AbstractLogger;
use ManaPHP\Logging\Logger\Log;

/** @noinspection SpellCheckingInspection */
//#/etc/rsyslog.d/99-app.conf
//$template myformat,"%msg%\n"
//$ActionFileDefaultTemplate myformat
//
//$template myTemplate,"/var/log/test/%PROGRAMNAME%.log"
//user.*  ?myTemplate

class Syslog extends AbstractLogger
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
        $this->host = $parts['host'];
        $this->scheme = $parts['scheme'] ?? 'udp';
        $this->port = $parts['port'] ? (int)$parts['port'] : 514;

        if ($this->scheme !== 'udp') {
            throw new NotSupportedException('only support udp protocol');
        }

        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    }

    public function __destruct()
    {
        if ($this->socket !== null) {
            socket_close($this->socket);
        }
    }

    public function append(Log $log): void
    {
        $severity = ['fatal' => LOG_CRIT,
                     'error' => LOG_ERR,
                     'warn'  => LOG_WARNING,
                     'info'  => LOG_INFO,
                     'debug' => LOG_DEBUG,
                    ][$log->level];

        $host = $this->host;
        $port = $this->port;
        $tag = $this->app_id;

        $priority = $this->facility * 8 + $severity;
        $timestamp = date('M d H:i:s', (int)$log->timestamp);

        $replaced = [];

        preg_match_all('#:(\w+)#', $this->line_format, $matches);
        foreach ($matches[1] as $key) {
            if ($key !== 'message') {
                $replaced[":$key"][] = $log->$key ?? '-';
            }
        }

        if ($log->category === 'exception') {
            foreach (preg_split('#[\\r\\n]+#', $log->message) as $line) {
                $replaced[':message'] = $line;
                $content = strtr($this->line_format, $replaced);

                // <PRI>TIMESTAMP HOST TAG:CONTENT
                $packet = "<$priority>$timestamp $log->hostname $tag:$content";
                socket_sendto($this->socket, $packet, \strlen($packet), 0, $host, $port);
            }
        } else {
            $replaced[':message'] = $log->message;
            $content = strtr($this->line_format, $replaced);

            // <PRI>TIMESTAMP HOST TAG:CONTENT
            $packet = "<$priority>$timestamp $log->hostname $tag:$content";
            socket_sendto($this->socket, $packet, \strlen($packet), 0, $host, $port);
        }
    }
}
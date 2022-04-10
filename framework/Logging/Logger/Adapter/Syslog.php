<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Logging\AbstractLogger;
use ManaPHP\Logging\Level;
use ManaPHP\Logging\Logger\Log;

/** @noinspection SpellCheckingInspection */
//#/etc/rsyslog.d/99-app.conf
//$template myformat,"%msg%\n"
//$ActionFileDefaultTemplate myformat
//
//$template myTemplate,"/var/log/test/%PROGRAMNAME%.log"
//user.*  ?myTemplate

/**
 * @property-read \ManaPHP\ConfigInterface $config
 */
class Syslog extends AbstractLogger
{
    protected string $uri;
    protected int $facility;
    protected string $format;
    protected string $scheme;
    protected string $host;
    protected int $port;

    protected mixed $socket;

    public function __construct(string $uri, int $facility = 1,
        string $format = '[:date][:client_ip][:request_id16][:level][:category][:location] :message',
        string $level = Level::DEBUG, ?string $hostname = null
    ) {
        parent::__construct($level, $hostname);

        $this->uri = $uri;
        $this->facility = $facility;
        $this->format = $format;

        $parts = parse_url($uri);
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
        $tag = $this->config->get('id');

        $priority = $this->facility * 8 + $severity;
        $timestamp = date('M d H:i:s', (int)$log->timestamp);

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
            foreach (preg_split('#[\\r\\n]+#', $log->message) as $line) {
                $replaced[':message'] = $line;
                $content = strtr($this->format, $replaced);

                // <PRI>TIMESTAMP HOST TAG:CONTENT
                $packet = "<$priority>$timestamp $log->hostname $tag:$content";
                socket_sendto($this->socket, $packet, strlen($packet), 0, $host, $port);
            }
        } else {
            $replaced[':message'] = $log->message;
            $content = strtr($this->format, $replaced);

            // <PRI>TIMESTAMP HOST TAG:CONTENT
            $packet = "<$priority>$timestamp $log->hostname $tag:$content";
            socket_sendto($this->socket, $packet, strlen($packet), 0, $host, $port);
        }
    }
}
<?php

namespace ManaPHP\Logging\Logger\Adapter;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Logging\Logger;

/** @noinspection SpellCheckingInspection */
//#/etc/rsyslog.d/99-app.conf
//$template myformat,"%msg%\n"
//$ActionFileDefaultTemplate myformat
//
//$template myTemplate,"/var/log/test/%PROGRAMNAME%.log"
//user.*  ?myTemplate

class Syslog extends Logger
{
    /**
     * @var string
     */
    protected $uri;

    /**
     * @var int
     */
    protected $facility = 1;
    /**
     * @var string
     */
    protected $format = '[:date][:client_ip][:request_id16][:level][:category][:location] :message';

    /**
     * @var string
     */
    protected $scheme = 'udp';

    /**
     * @var int
     */
    protected $host;

    /**
     * @var string
     */
    protected $port = 514;

    /**
     * @var resource
     */
    protected $socket;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        if (!isset($options['uri'])) {
            throw new MisuseException('`uri` is not assign');
        }

        $this->uri = $options['uri'];
        $parts = parse_url($options['uri']);
        $this->host = $parts['host'];
        if (isset($parts['scheme'])) {
            $this->scheme = $parts['scheme'];
        }
        if (isset($parts['port'])) {
            $this->port = (int)$parts['port'];
        }

        if ($this->scheme !== 'udp') {
            throw new NotSupportedException('only support udp protocol');
        }

        if (isset($options['facility'])) {
            $this->facility = $options['facility'];
        }

        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    }

    public function __destruct()
    {
        if ($this->socket !== null) {
            socket_close($this->socket);
        }
    }

    public function append($logs)
    {
        static $map;
        if ($map === null) {
            $map = [
                'fatal' => LOG_CRIT,
                'error' => LOG_ERR,
                'warn'  => LOG_WARNING,
                'info'  => LOG_INFO,
                'debug' => LOG_DEBUG,
            ];
        }

        $host = $this->host;
        $port = $this->port;
        $tag = $this->configure->id;

        foreach ($logs as $log) {
            $severity = $map[$log->level];
            $priority = $this->facility * 8 + $severity;
            $timestamp = date('M d H:i:s', $log->timestamp);

            $replaced = [];

            $ms = sprintf('.%03d', ($log->timestamp - (int)$log->timestamp) * 1000);
            $replaced[':date'] = date('Y-m-d\TH:i:s', $log->timestamp) . $ms;
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
}
<?php

namespace ManaPHP\Logger\Adapter;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Logger;

/** @noinspection SpellCheckingInspection */
//#/etc/rsyslog.d/99-app.conf
//$template myformat,"%msg%\n"
//$ActionFileDefaultTemplate myformat
//
//$template myTemplate,"/var/log/test/%PROGRAMNAME%.log"
//user.*  ?myTemplate

/**
 * Class Syslog
 *
 * @package ManaPHP\Logger\Adapter
 *
 */
class Syslog extends Logger
{
    /**
     * @var string
     */
    protected $_receiver;

    /**
     * @var int
     */
    protected $_facility = 1;
    /**
     * @var string
     */
    protected $_format = '[:date][:client_ip][:request_id16][:level][:category][:location] :message';

    /**
     * @var string
     */
    protected $_receiver_protocol = 'udp';

    /**
     * @var int
     */
    protected $_receiver_host;

    /**
     * @var string
     */
    protected $_receiver_port = 514;

    /**
     * @var resource
     */
    protected $_socket;

    /**
     * Syslog constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        if (!isset($options['receiver'])) {
            throw new MisuseException('syslog receiver is not assign');
        }

        $this->_receiver = $options['receiver'];
        $parts = parse_url($options['receiver']);
        $this->_receiver_host = $parts['host'];
        if (isset($parts['scheme'])) {
            $this->_receiver_protocol = $parts['scheme'];
        }
        if (isset($parts['port'])) {
            $this->_receiver_port = (int)$parts['port'];
        }

        if ($this->_receiver_protocol !== 'udp') {
            throw new NotSupportedException('only support udp protocol');
        }

        if (isset($options['facility'])) {
            $this->_facility = $options['facility'];
        }

        $this->_socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    }

    public function __destruct()
    {
        if ($this->_socket !== null) {
            socket_close($this->_socket);
        }
    }

    public function append($logs)
    {
        static $map;
        if ($map === null) {
            $map = [
                'fatal' => LOG_CRIT,
                'error' => LOG_ERR,
                'warn' => LOG_WARNING,
                'info' => LOG_INFO,
                'debug' => LOG_DEBUG,
            ];
        }

        $host = $this->_receiver_host;
        $port = $this->_receiver_port;
        $tag = $this->configure->id;

        foreach ($logs as $log) {
            $severity = $map[$log->level];
            $priority = $this->_facility * 8 + $severity;
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
                    $content = strtr($this->_format, $replaced);

                    // <PRI>TIMESTAMP HOST TAG:CONTENT
                    $packet = "<$priority>$timestamp $log->host $tag:$content";
                    socket_sendto($this->_socket, $packet, strlen($packet), 0, $host, $port);
                }
            } else {
                $replaced[':message'] = $log->message;
                $content = strtr($this->_format, $replaced);

                // <PRI>TIMESTAMP HOST TAG:CONTENT
                $packet = "<$priority>$timestamp $log->host $tag:$content";
                socket_sendto($this->_socket, $packet, strlen($packet), 0, $host, $port);
            }
        }
    }
}
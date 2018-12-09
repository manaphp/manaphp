<?php
namespace ManaPHP\Plugins;

use ManaPHP\Logger;
use ManaPHP\Plugin;

class FiddlerPlugin extends Plugin
{
    const PROCESSOR_PREFIX = 'process_';

    /**
     * @var string
     */
    protected $_channel;

    /**
     * @var bool
     */
    protected $_enabled = true;

    /**
     * @var array
     */
    protected $_header;

    /**
     * @var float
     */
    protected $_last_checked;

    public function init()
    {
        $this->eventsManager->attachEvent('logger:log', [$this, 'onLoggerLog']);
        $this->eventsManager->attachEvent('app:beginRequest', [$this, 'checkEnabled']);
        $this->eventsManager->attachEvent('app:beginRequest', [$this, 'onBeginRequest']);
        $this->eventsManager->attachEvent('response:afterSend', [$this, 'onAfterSendResponse']);
        $this->_header = ['ip' => '-', 'url' => '-', 'uuid' => '-'];
        $this->_channel = 'manaphp:fiddler:cli:' . $this->configure->id;
    }

    public function checkEnabled()
    {
        $this->_header = [
            'ip' => $this->request->getClientIp(),
            'url' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            'uuid' => substr(md5($_SERVER['REQUEST_TIME_FLOAT'] . mt_rand()), 0, 8)
        ];
        $this->_channel = 'manaphp:fiddler:web:' . $this->configure->id . ':' . $this->request->getClientIp();

        $current = microtime(true);
        if ($current - $this->_last_checked >= 1.0) {
            $this->_last_checked = $current;
            $this->_enabled = $this->publish('ping', ['timestamp' => round($current, 3)]) > 0;
        }
        
        if ($this->_enabled) {
            $this->logger->setLevel(Logger::LEVEL_DEBUG);
        }
    }

    public function onBeginRequest()
    {
        if ($this->enabled()) {
            $server = [];
            foreach ($_SERVER as $k => $v) {
                if (strpos($k, 'HTTP_') !== 0) {
                    continue;
                }
                $server[$k] = $v;
            }

            $this->publish('request', ['GET' => $_GET, 'POST' => $_POST, 'SERVER' => $server]);
        }
    }

    /**
     * @param \ManaPHP\LoggerInterface $logger
     * @param \ManaPHP\Logger\Log      $log
     */
    public function onLoggerLog($logger, $log)
    {
        0 && $logger;

        if ($this->enabled()) {
            $data = [
                'category' => $log->category,
                'location' => $log->location,
                'level' => $log->level,
                'message' => $log->message,
            ];
            $this->publish('logger', $data);
        }
    }

    /**
     * @return bool
     */
    public function enabled()
    {
        return $this->_enabled;
    }

    /**
     * @param \ManaPHP\Http\ResponseInterface $response
     */
    public function onAfterSendResponse($response)
    {
        if ($this->enabled()) {
            /** @var \ManaPHP\Http\ResponseInterface $source */
            $data = [
                'uri' => $_SERVER['REQUEST_URI'],
                'code' => $response->getStatusCode(),
                'content-type' => $response->getContentType(),
                'body' => $response->getContent(),
                'elapsed' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3)];
            $this->publish('response', $data);
        }
    }

    /**
     * @param string $type
     * @param array  $data
     *
     * @return int
     */
    public function publish($type, $data)
    {
        $packet = $this->_header;

        $packet['type'] = $type;
        $packet['data'] = $data;

        /** @noinspection PhpUndefinedMethodInspection */
        /** @var \Redis $redis */
        $redis = $this->redis->getConnection();
        $r = $redis->publish($this->_channel, json_encode($packet, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if ($r <= 0) {
            $this->_enabled = false;
            $this->_last_checked = microtime(true);
        }

        return $r;
    }

    /**
     * @param array $options
     */
    public function subscribeWeb($options = [])
    {
        $id = isset($options['id']) ? $options['id'] : $this->configure->id;
        if (isset($options['ip'])) {
            $channel = 'manaphp:fiddler:web:' . $id . ":$options[ip]";
        } else {
            $channel = 'manaphp:fiddler:web:' . $id . ':*';
        }

        /** @noinspection PhpUndefinedMethodInspection */
        /** @var \Redis $redis */
        $redis = $this->redis->getConnection();
        if (strpos($channel, '*') === false) {
            echo "subscribe on `$channel`", PHP_EOL, PHP_EOL;
            $redis->subscribe([$channel], function ($redis, $chan, $packet) {
                0 && $redis && $chan;

                $this->processMessage($packet);
            });
        } else {
            echo "psubscribe on `$channel`", PHP_EOL, PHP_EOL;
            $redis->psubscribe([$channel], function ($redis, $pattern, $chan, $packet) {
                0 && $redis && $chan && $pattern;

                $this->processMessage($packet);
            });
        }
    }

    /**
     * @param array $options
     */
    public function subscribeCli($options = [])
    {
        $id = isset($options['id']) ? $options['id'] : $this->configure->id;
        $channel = 'manaphp:fiddler:cli:' . $id;

        /** @noinspection PhpUndefinedMethodInspection */
        /** @var \Redis $redis */
        $redis = $this->redis->getConnection();
        echo "subscribe on `$channel`", PHP_EOL, PHP_EOL;
        $redis->subscribe([$channel], function ($redis, $chan, $packet) {
            0 && $redis && $chan;
            $this->processMessage($packet);
        });
    }

    public function processMessage($packet)
    {
        $ts = microtime(true);

        $message = json_decode($packet, true);

        $ip = $message['ip'];
        $type = $message['type'];
        $uuid = $message['uuid'];

        $processor = self::PROCESSOR_PREFIX . $type;
        if (!method_exists($this, $processor)) {
            $processor = self::PROCESSOR_PREFIX . 'default';
        }

        $date = date('H:i:s.', $ts) . sprintf('%03d', ($ts - (int)$ts) * 1000);
        $body = $this->$processor($message['data']);
        echo "[$ip][$date][$uuid][$type]$body", PHP_EOL;
    }

    /**
     * @param array $data
     *
     * @return string
     */
    public function process_ping($data)
    {
        $ts = $data['timestamp'];

        return 'remote time: ' . date('c', $ts) . sprintf('.%03d', ($ts - (int)$ts) * 1000);
    }

    /**
     * @param array $data
     *
     * @return string
     */
    public function process_response($data)
    {
        $format = '[:uri][:elapsed][:code][:content-type] :body';

        $replaced = [];
        $replaced[':uri'] = $data['uri'];
        $replaced[':elapsed'] = $data['elapsed'];
        $replaced[':code'] = $data['code'];
        $replaced[':content-type'] = $data['content-type'];
        $replaced[':body'] = $data['body'];

        return strtr($format, $replaced);
    }

    /**
     * @param array $data
     *
     * @return string
     */
    public function process_default($data)
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array $log
     *
     * @return string
     */
    public function process_logger($log)
    {
        $format = '[:level][:category][:location] :message';
        $replaced = [];

        $replaced[':category'] = $log['category'];
        $replaced[':location'] = $log['location'];
        $replaced[':level'] = strtoupper($log['level']);
        $replaced[':message'] = $log['message'];

        return strtr($format, $replaced);
    }
}
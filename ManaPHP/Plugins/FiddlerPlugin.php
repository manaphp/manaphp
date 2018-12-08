<?php
namespace ManaPHP\Plugins;

use ManaPHP\Plugin;

class FiddlerPlugin extends Plugin
{
    const PROCESSOR_PREFIX = 'process_';

    /**
     * @var string
     */
    protected $_entry = 'fiddler:';

    /**
     * @var bool
     */
    protected $_enabled = false;

    /**
     * @var array
     */
    protected $_header;

    /**
     * FiddlerPlugin constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['entry'])) {
            $this->_entry = $options['entry'];
        }
    }

    public function init()
    {
        $this->eventsManager->peekEvent([$this, 'peek']);
        $this->eventsManager->attachEvent('app:beginRequest', [$this, 'onBeginRequest']);
    }

    public function onBeginRequest()
    {
        $this->publish_onBeginRequest();
    }

    public function peek($event, $source, $data)
    {
        if (!$this->_enabled) {
            return;
        }

        if ($event === 'logger:log') {
            $this->publish_onLoggerLog($data);
        } elseif ($event === 'response:afterSend') {
            $this->publish_onAfterSendResponse($source);
        }
    }

    /**
     * @param \ManaPHP\Logger\Log $log
     */
    public function publish_onLoggerLog($log)
    {
        $data = [
            'category' => $log->category,
            'location' => $log->location,
            'level' => $log->level,
            'message' => $log->message,
        ];
        $this->publish('logger', $data);
    }

    public function publish_onBeginRequest()
    {
        $this->_header = [
            'ip' => $this->request->getClientIp(),
            'url' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            'uuid' => substr(md5($_SERVER['REQUEST_TIME_FLOAT'] . mt_rand()), 0, 8)
        ];

        $server = [];
        foreach ($_SERVER as $k => $v) {
            if (strpos($k, 'HTTP_') !== 0) {
                continue;
            }
            $server[$k] = $v;
        }
        $this->_enabled = $this->publish('request', ['GET' => $_GET, 'POST' => $_POST, 'SERVER' => $server]) > 0;
    }

    /**
     * @param \ManaPHP\Http\ResponseInterface $response
     */
    public function publish_onAfterSendResponse($response)
    {
        /** @var \ManaPHP\Http\ResponseInterface $source */
        $data = [
            'uri' => $_SERVER['REQUEST_URI'],
            'code' => $response->getStatusCode(),
            'content-type' => $response->getContentType(),
            'body' => $response->getContent(),
            'elapsed' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3)];
        $this->publish('response', $data);
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
        return $redis->publish($this->_entry . $this->configure->id, json_encode($packet, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param $filters
     */
    public function subscribe($filters = [])
    {
        0 && $filters;

        /** @noinspection PhpUndefinedMethodInspection */
        /** @var \Redis $redis */
        $redis = $this->redis->getConnection();

        $redis->subscribe([$this->_entry . $this->configure->id], [$this, 'processMessage']);
    }

    public function processMessage($redis, $channel, $packet)
    {
        0 && $redis && $channel;

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
<?php
namespace ManaPHP\Plugins;

use ManaPHP\Plugin;

class SlowlogPlugin extends Plugin
{
    /**
     * @var array
     */
    protected $_threshold = [
        'request' => 1,
    ];

    /**
     * @var string
     */
    protected $_format = '[:date][:client_ip][:request_id16][:elapsed] :message';

    public function __construct($options = null)
    {
        if (isset($options['threshold'])) {
            $this->_threshold = $options['threshold'] + $this->_threshold;
        }

        $this->eventsManager->attachEvent('request:destruct', [$this, 'onDestruct']);
    }

    protected function _write($type, $elapsed, $message)
    {
        $elapsed = round($elapsed, 3);

        $request_id = $this->response->getHeader('X-Request-Id');
        $replaced = [];
        $replaced[':date'] = date('Y-m-d\TH:i:s') . sprintf('%.03f', explode(' ', microtime())[0]);
        $replaced[':client_ip'] = $this->request->getClientIp();
        $replaced[':request_id16'] = substr($request_id, 0, 16);
        $replaced[':request_id'] = $request_id;
        $replaced[':elapsed'] = sprintf('%.03f', $elapsed);
        $replaced[':message'] = (is_string($message) ? $message : json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . PHP_EOL;

        $str = strtr($this->_format, $replaced);

        $file = $this->alias->resolve("@data/slowlog/{$type}.log");
        if (!is_file($file)) {
            $dir = dirname($file);
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
                /** @noinspection ForgottenDebugOutputInspection */
                trigger_error("Unable to create $dir directory: " . error_get_last()['message'], E_USER_WARNING);
            }
        }

        if (file_put_contents($file, $str, FILE_APPEND | LOCK_EX) === false) {
            /** @noinspection ForgottenDebugOutputInspection */
            trigger_error('Write log to file failed: ' . $file, E_USER_WARNING);
        }
    }

    /**
     * @param float $elapsed
     * @param float $precision
     *
     * @return string
     */
    protected function _getEid($elapsed, $precision = 0.1)
    {
        $id = '';
        for ($level = 0; $level < 3; $level++) {
            /** @noinspection PowerOperatorCanBeUsedInspection */
            $current = $precision * pow(10, $level);
            if ($current >= 10) {
                break;
            }
            $count = min($elapsed / $current, 10);
            for ($i = 1; $i < $count; $i++) {
                $id .= 't' . (($current >= 1) ? $current * $i : substr(1 / $current, 1) . $i);
            }
        }

        return $id;
    }

    public function onDestruct()
    {
        if ($this->response->hasHeader('X-Response-Time')) {
            $elapsed = $this->response->getHeader('X-Response-Time');
        } else {
            $elapsed = microtime(true) - $this->request->getServer('REQUEST_TIME_FLOAT');
        }

        if ($this->_threshold['request'] > $elapsed) {
            return;
        }

        $request = $this->request->get();
        if (isset($request['_url'])) {
            $url = $request['_url'];
            unset($request['_url']);
        } else {
            $url = '/';
        }

        $message = [
            'method' => $this->request->getServer('REQUEST_METHOD'),
            'uri' => $url,
            '_REQUEST' => $request,
            'host' => $this->request->getServer('HTTP_HOST'),
            'eid' => $this->_getEid($elapsed)];

        $this->_write('request', $elapsed, $message);
    }
}
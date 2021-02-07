<?php

namespace ManaPHP\Http;

use ManaPHP\Helper\LocalFS;
use ManaPHP\Plugin;

/**
 * @property-read \ManaPHP\Configuration\Configure $configure
 */
class SlowlogPlugin extends Plugin
{
    /**
     * @var float
     */
    protected $threshold = 1.0;

    /**
     * @var string
     */
    protected $file = '@data/slowlogPlugin/{id}.log';

    /**
     * @var string
     */
    protected $format = '[:date][:client_ip][:request_id][:elapsed] :message';

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['threshold'])) {
            $this->threshold = (float)$options['threshold'];
        }

        if (isset($options['file'])) {
            $this->file = $options['file'];
        }

        $this->file = strtr($this->file, ['{id}' => $this->configure->id]);

        if (isset($options['format'])) {
            $this->format = $options['format'];
        }

        $this->attachEvent('request:end', [$this, 'onRequestEnd']);
    }

    /**
     * @param float $elapsed
     * @param mixed $message
     *
     * @return void
     */
    protected function write($elapsed, $message)
    {
        $elapsed = round($elapsed, 3);

        if (!is_string($message)) {
            $message = json_stringify($message, JSON_PARTIAL_OUTPUT_ON_ERROR);
        }

        $replaced = [];
        $ts = microtime(true);
        $replaced[':date'] = date('Y-m-d\TH:i:s', $ts) . sprintf('.%03d', ($ts - (int)$ts) * 1000);
        $replaced[':client_ip'] = $this->request->getClientIp();
        $replaced[':request_id'] = $this->request->getRequestId();
        $replaced[':elapsed'] = sprintf('%.03f', $elapsed);
        $replaced[':message'] = $message . PHP_EOL;

        LocalFS::fileAppend($this->file, strtr($this->format, $replaced));
    }

    /**
     * @param float $elapsed
     * @param float $precision
     *
     * @return string
     */
    protected function getEid($elapsed, $precision = 0.1)
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

    /**
     * @return void
     */
    public function onRequestEnd()
    {
        if ($this->response->hasHeader('X-Response-Time')) {
            $elapsed = $this->response->getHeader('X-Response-Time');
        } else {
            $elapsed = $this->request->getElapsedTime();
        }

        if ($this->threshold > $elapsed) {
            return;
        }

        $dispatcher = $this->dispatcher;
        $route = implode('::', [$dispatcher->getArea(), $dispatcher->getController(), $dispatcher->getAction()]);

        $message = [
            'method'   => $this->request->getMethod(),
            'route'    => $route,
            'url'      => $this->request->getUrl(),
            '_REQUEST' => $this->request->get(),
            'eid'      => $this->getEid($elapsed)
        ];

        $this->write($elapsed, $message);
    }
}
<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filters;

use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Server\Event\RequestEnd;

class Slowlog
{
    #[Autowired] protected ConfigInterface $config;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected DispatcherInterface $dispatcher;

    #[Autowired] protected float $threshold = 1.0;
    #[Autowired] protected string $file = '@runtime/slowlogPlugin/{id}.log';
    #[Autowired] protected string $format = '[:date][:client_ip][:request_id][:elapsed] :message';

    protected function write(float $elapsed, mixed $message): void
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

        LocalFS::fileAppend(strtr($this->file, ['{id}' => $this->config->get('id')]), strtr($this->format, $replaced));
    }

    public function onEnd(#[Event] RequestEnd $event): void
    {
        if ($event->response->hasHeader('X-Response-Time')) {
            $elapsed = $event->response->getHeader('X-Response-Time');
        } else {
            $elapsed = $event->request->getElapsedTime();
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
            '_REQUEST' => $this->request->all(),
            'elapsed'  => $elapsed,
        ];

        $this->write($elapsed, $message);
    }
}
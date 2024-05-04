<?php
declare(strict_types=1);

namespace ManaPHP\Http\Middlewares;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Server\Event\RequestEnd;
use function is_string;

class SlowlogMiddleware
{
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected DispatcherInterface $dispatcher;

    #[Autowired] protected float $threshold = 1.0;
    #[Autowired] protected string $file = '@runtime/slowlog/{app_id}.log';
    #[Autowired] protected string $format = '[:date][:client_ip][:request_id][:elapsed] :message';

    #[Config] protected string $app_id;

    protected function write(float $elapsed, mixed $message): void
    {
        $elapsed = round($elapsed, 3);

        if (!is_string($message)) {
            $message = json_stringify($message, JSON_PARTIAL_OUTPUT_ON_ERROR);
        }

        $replaced = [];
        $ts = microtime(true);
        $replaced[':date'] = date('Y-m-d\TH:i:s', $ts) . sprintf('.%03d', ($ts - (int)$ts) * 1000);
        $replaced[':client_ip'] = $this->request->ip();
        $replaced[':request_id'] = $this->request->header('x-request-id', '');
        $replaced[':elapsed'] = sprintf('%.03f', $elapsed);
        $replaced[':message'] = $message . PHP_EOL;

        LocalFS::fileAppend(strtr($this->file, ['{app_id}' => $this->app_id]), strtr($this->format, $replaced));
    }

    public function onEnd(#[Event] RequestEnd $event): void
    {
        if ($event->response->hasHeader('X-Response-Time')) {
            $elapsed = $event->response->getHeader('X-Response-Time');
        } else {
            $elapsed = $event->request->elapsed();
        }

        if ($this->threshold > $elapsed) {
            return;
        }

        $dispatcher = $this->dispatcher;

        $message = [
            'method'   => $this->request->method(),
            'handler'  => (string)$dispatcher->getHandler(),
            'url'      => $this->request->url(),
            '_REQUEST' => $this->request->all(),
            'elapsed'  => $elapsed,
        ];

        $this->write($elapsed, $message);
    }
}
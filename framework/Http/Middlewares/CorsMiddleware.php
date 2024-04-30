<?php
declare(strict_types=1);

namespace ManaPHP\Http\Middlewares;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Exception\AbortException;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Server\Event\RequestBegin;

class CorsMiddleware
{
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;

    #[Autowired] protected int $max_age = 86400;
    #[Autowired] protected ?string $origin;
    #[Autowired] protected bool $credentials = true;

    #[Config] protected string $env_app;

    public function onBegin(#[Event] RequestBegin $event): void
    {
        SuppressWarnings::unused($event);

        $origin = $this->request->origin();
        $host = $this->request->header('host');

        if ($origin !== '' && $origin !== $host) {
            if ($this->origin) {
                $allow_origin = $this->origin;
            } elseif ($this->env_app === 'prod') {
                $origin_pos = strpos($origin, '.');
                $host_pos = strpos($host, '.');

                if (($origin_pos !== false && $host_pos !== false)
                    && substr($origin, $origin_pos) === substr($host, $host_pos)
                ) {
                    $allow_origin = $origin;
                } else {
                    $allow_origin = '*';
                }
            } else {
                $allow_origin = $origin;
            }

            $allow_headers = 'Origin, Accept, Authorization, Content-Type, X-Requested-With';
            $allow_methods = 'HEAD,GET,POST,PUT,DELETE';
            $this->response
                ->setHeader('Access-Control-Allow-Origin', $allow_origin)
                ->setHeader('Access-Control-Allow-Credentials', $this->credentials ? 'true' : 'false')
                ->setHeader('Access-Control-Allow-Headers', $allow_headers)
                ->setHeader('Access-Control-Allow-Methods', $allow_methods)
                ->setHeader('Access-Control-Max-Age', (string)$this->max_age);
        }

        if ($this->request->method() === 'OPTIONS') {
            throw new AbortException();
        }
    }
}
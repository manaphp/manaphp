<?php
declare(strict_types=1);

namespace ManaPHP\Filters;

use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Exception\AbortException;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Server\Event\RequestBegin;

class CorsFilter
{
    #[Inject] protected ConfigInterface $config;
    #[Inject] protected RequestInterface $request;
    #[Inject] protected ResponseInterface $response;

    #[Value] protected int $max_age = 86400;
    #[Value] protected ?string $origin;
    #[Value] protected bool $credentials = true;

    public function onBegin(#[Event] RequestBegin $event): void
    {
        $origin = $this->request->getServer('HTTP_ORIGIN');
        $host = $this->request->getServer('HTTP_HOST');

        if ($origin !== '' && $origin !== $host) {
            if ($this->origin) {
                $allow_origin = $this->origin;
            } elseif ($this->config->get('env') === 'prod') {
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

        if ($this->request->isOptions()) {
            throw new AbortException();
        }
    }
}
<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter\Native;

use ManaPHP\AliasInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\Response\AppenderInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\RouterInterface;
use ManaPHP\Http\Server\Event\RequestResponded;
use ManaPHP\Http\Server\Event\RequestResponsing;
use ManaPHP\Http\Server\Event\ResponseStringify;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class Sender implements SenderInterface
{
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected AliasInterface $alias;
    #[Autowired] protected RouterInterface $router;

    public function send(): void
    {
        if (headers_sent($file, $line)) {
            throw new MisuseException("Headers has been sent in $file:$line");
        }

        if (!\is_string($this->response->getContent()) && !$this->response->hasFile()) {
            $this->eventDispatcher->dispatch(new ResponseStringify($this->response));
            if (!\is_string($content = $this->response->getContent())) {
                $this->response->setContent(json_stringify($content));
            }
        }

        $this->eventDispatcher->dispatch(new RequestResponsing($this->request, $this->response));

        foreach ($this->response->getAppenders() as $appender) {
            /** @var string|AppenderInterface $appender */
            $appender = $this->container->get($appender);
            $appender->append($this->request, $this->response);
        }

        header('HTTP/1.1 ' . $this->response->getStatus());

        foreach ($this->response->getHeaders() as $header => $value) {
            if ($value !== null) {
                header($header . ': ' . $value);
            } else {
                header($header);
            }
        }

        $prefix = $this->router->getPrefix();
        foreach ($this->response->getCookies() as $cookie) {
            setcookie(
                $cookie['name'],
                $cookie['value'],
                $cookie['expire'],
                $cookie['path'] === '' ? '' : ($prefix . $cookie['path']),
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly']
            );
        }

        $content = $this->response->getContent();
        if ($this->response->getStatusCode() === 304) {
            null;
        } elseif ($this->request->method() === 'HEAD') {
            header('Content-Length: ' . \strlen($content));
        } elseif ($file = $this->response->getFile()) {
            readfile($this->alias->resolve($file));
        } else {
            echo $content;
        }

        $this->eventDispatcher->dispatch(new RequestResponded($this->request, $this->response));
    }
}
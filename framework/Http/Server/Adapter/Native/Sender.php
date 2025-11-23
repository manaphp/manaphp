<?php

declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter\Native;

use ManaPHP\Alias\Path;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\RouterInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function header;
use function headers_sent;
use function readfile;
use function setcookie;
use function strlen;

class Sender implements SenderInterface
{
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected RouterInterface $router;

    public function sendHeaders(): void
    {
        if (headers_sent($file, $line)) {
            throw new MisuseException('Headers have already been sent in "{file}" at line {line}.', ['file' => $file, 'line' => $line]);
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
    }

    public function sendBody(): void
    {
        $content = $this->response->getContent() ?? '';
        if ($this->response->getStatusCode() === 304) {
            SuppressWarnings::noop();
        } elseif ($this->request->method() === 'HEAD') {
            header('Content-Length: ' . strlen($content));
        } elseif ($file = $this->response->getFile()) {
            readfile(Path::resolve($file));
        } else {
            echo $content;
        }
    }
}

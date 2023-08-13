<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter\Native;

use ManaPHP\AliasInterface;
use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Event\EventTrait;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\RouterInterface;

class Sender extends Component implements SenderInterface
{
    use EventTrait;

    #[Inject] protected RequestInterface $request;
    #[Inject] protected ResponseInterface $response;
    #[Inject] protected AliasInterface $alias;
    #[Inject] protected RouterInterface $router;

    public function send(): void
    {
        if (headers_sent($file, $line)) {
            throw new MisuseException("Headers has been sent in $file:$line");
        }

        if (!is_string($this->response->getContent()) && !$this->response->hasFile()) {
            $this->fireEvent('response:stringify');
            if (!is_string($content = $this->response->getContent())) {
                $this->response->setContent(json_stringify($content));
            }
        }

        $this->fireEvent('request:responding');

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

        header('X-Request-Id: ' . $this->request->getRequestId());
        header('X-Response-Time: ' . $this->request->getElapsedTime());

        $content = $this->response->getContent();
        if ($this->response->getStatusCode() === 304) {
            null;
        } elseif ($this->request->isHead()) {
            header('Content-Length: ' . strlen($content));
        } elseif ($file = $this->response->getFile()) {
            readfile($this->alias->resolve($file));
        } else {
            echo $content;
        }

        $this->fireEvent('request:responded');
    }
}
<?php
declare(strict_types=1);

namespace ManaPHP\Rpc\Http\Server\Adapter;

use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Rpc\Http\AbstractServer;

class Fpm extends AbstractServer
{
    protected function prepareGlobals(): void
    {
        $raw_body = file_get_contents('php://input');
        $this->globals->prepare($_GET, $_POST, $_SERVER, $raw_body);
    }

    public function start(): void
    {
        $this->prepareGlobals();

        if ($this->authenticate()) {
            $this->rpcHandler->handle();
        } else {
            $this->send();
        }
    }

    public function send(): void
    {
        header('HTTP/1.1 ' . $this->response->getStatus());

        foreach ($this->response->getHeaders() as $header => $value) {
            header($value === null ? $header : "$header: $value");
        }

        header('X-Request-Id: ' . $this->request->getRequestId());
        header('X-Response-Time: ' . $this->request->getElapsedTime());

        if ($this->response->hasFile()) {
            throw new NotSupportedException('rpc not support send file');
        }

        $content = $this->response->getContent();
        echo is_string($content) ? $content : json_stringify($content);
    }
}
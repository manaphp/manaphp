<?php

namespace ManaPHP\Rpc\Http\Server\Adapter;

use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Rpc\Http\AbstractServer;

class Fpm extends AbstractServer
{
    /**
     * @return void
     */
    protected function prepareGlobals()
    {
        $raw_body = file_get_contents('php://input');
        $this->request->prepare($_GET, $_POST, $_SERVER, $raw_body);
    }

    /**
     * @return static
     */
    public function start()
    {
        $this->prepareGlobals();

        if ($this->authenticate()) {
            $this->rpcHandler->handle();
        } else {
            $this->send();
        }

        return $this;
    }

    /**
     * @return static
     */
    public function send()
    {
        header('HTTP/1.1 ' . $this->response->getStatus());

        foreach ($this->response->getHeaders() as $header => $value) {
            header($value === null ? $header : "$header: $value");
        }

        if ($this->response->hasCookies()) {
            throw new NotSupportedException('rpc not support cookies');
        }

        header('X-Request-Id: ' . $this->request->getRequestId());
        header('X-Response-Time: ' . $this->request->getElapsedTime());

        if ($this->response->hasFile()) {
            throw new NotSupportedException('rpc not support send file');
        }

        $content = $this->response->getContent();
        echo is_string($content) ? $content : json_stringify($content);

        return $this;
    }
}
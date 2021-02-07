<?php

namespace ManaPHP\Rpc\Server\Adapter;

use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Rpc\Server;

class Fpm extends Server
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
     * @param \ManaPHP\Rpc\Server\HandlerInterface $handler
     *
     * @return static
     */
    public function start($handler)
    {
        $this->handler = $handler;

        $this->prepareGlobals();

        if ($this->authenticate()) {
            $this->handler->handle();
        } else {
            $this->send($this->response->getContext());
        }

        return $this;
    }

    /**
     * @param \ManaPHP\Http\ResponseContext $response
     *
     * @return static
     */
    public function send($response)
    {
        header('HTTP/1.1 ' . $response->status_code . ' ' . $response->status_text);

        foreach ($response->headers as $header => $value) {
            header($value === null ? $header : "$header: $value");
        }

        if ($response->cookies) {
            throw new NotSupportedException('rpc not support cookies');
        }

        header('X-Request-Id: ' . $this->request->getRequestId());
        header('X-Response-Time: ' . $this->request->getElapsedTime());

        if ($response->file) {
            throw new NotSupportedException('rpc not support send file');
        }

        $content = $response->content;
        echo is_string($content) ? $content : json_stringify($content);

        return $this;
    }
}
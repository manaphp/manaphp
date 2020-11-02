<?php

namespace ManaPHP\Rpc\Server\Adapter;

use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Rpc\Server;

/**
 * Class Fpm
 *
 * @package ManaPHP\Rpc\Server\Adapter
 */
class Fpm extends Server
{
    protected function _prepareGlobals()
    {
        if (!isset($_GET['_url']) && ($pos = strpos($_SERVER['PHP_SELF'], '/index.php/')) !== false) {
            $_GET['_url'] = $_REQUEST['_url'] = '/index' . substr($_SERVER['PHP_SELF'], $pos + 10);
        }

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
        $this->_handler = $handler;

        $this->_prepareGlobals();

        if ($this->authenticate()) {
            $this->_handler->handle();
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

        $server = $this->request->getServer();

        header('X-Request-Id: ' . $this->request->getRequestId());
        header('X-Response-Time: ' . sprintf('%.3f', microtime(true) - $server['REQUEST_TIME_FLOAT']));

        if ($response->file) {
            throw new NotSupportedException('rpc not support send file');
        }

        $content = $response->content;
        echo is_string($content) ? $content : json_stringify($content);

        return $this;
    }
}
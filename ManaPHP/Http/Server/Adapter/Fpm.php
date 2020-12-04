<?php

namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Http\Server;

class Fpm extends Server
{
    protected function _prepareGlobals()
    {
        if (!isset($_GET['_url']) && ($pos = strpos($_SERVER['PHP_SELF'], '/index.php/')) !== false) {
            $_GET['_url'] = $_REQUEST['_url'] = '/index' . substr($_SERVER['PHP_SELF'], $pos + 10);
        }

        $rawBody = file_get_contents('php://input');
        $this->request->prepare($_GET, $_POST, $_SERVER, $rawBody, $_COOKIE, $_SERVER);

        if ($this->_use_globals) {
            $this->globalsManager->proxy();
        }
    }

    /**
     * @param \ManaPHP\Http\Server\HandlerInterface $handler
     *
     * @return void
     */
    public function start($handler)
    {
        $this->_prepareGlobals();

        $handler->handle();
    }

    /**
     * @param \ManaPHP\Http\ResponseContext $response
     *
     * @return static
     */
    public function send($response)
    {
        if (headers_sent($file, $line)) {
            throw new MisuseException("Headers has been sent in $file:$line");
        }

        $this->fireEvent('response:sending', $response);

        header('HTTP/1.1 ' . $response->status_code . ' ' . $response->status_text);

        foreach ($response->headers as $header => $value) {
            if ($value !== null) {
                header($header . ': ' . $value);
            } else {
                header($header);
            }
        }

        foreach ($response->cookies as $cookie) {
            setcookie(
                $cookie['name'],
                $cookie['value'],
                $cookie['expire'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly']
            );
        }

        header('X-Request-Id: ' . $this->request->getRequestId());
        header('X-Response-Time: ' . $this->request->getElapsedTime());

        if ($response->status_code === 304) {
            null;
        } elseif ($this->request->isHead()) {
            header('Content-Length: ' . strlen($response->content));
        } elseif ($response->file) {
            readfile($this->alias->resolve($response->file));
        } else {
            echo $response->content;
        }

        $this->fireEvent('response:sent', $response);

        return $this;
    }
}

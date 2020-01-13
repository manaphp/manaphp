<?php
namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Http\Server;

/**
 * Class Fpm
 * @package ManaPHP\Http\Server\Adapter
 */
class Fpm extends Server
{
    protected function _prepareGlobals()
    {
        if (!isset($_GET['_url']) && ($pos = strpos($_SERVER['PHP_SELF'], '/index.php/')) !== false) {
            $_GET['_url'] = $_REQUEST['_url'] = '/index' . substr($_SERVER['PHP_SELF'], $pos + 10);
        }

        $globals = $this->request->getGlobals();

        if (!$_POST && isset($_SERVER['REQUEST_METHOD']) && !in_array($_SERVER['REQUEST_METHOD'], ['GET', 'OPTIONS'], true)) {
            $globals->rawBody = $rawBody = file_get_contents('php://input');

            if (isset($_SERVER['CONTENT_TYPE'])
                && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
                $_POST = json_parse($rawBody);
            } else {
                parse_str($rawBody, $_POST);
            }

            if (is_array($_POST)) {
                $_REQUEST = $_POST + $_GET;
            } else {
                $_POST = [];
            }
        }

        $this->request->setRequestId($_SERVER['HTTP_X_REQUEST_ID'] ?? null);

        $globals->_GET = $_GET;
        $globals->_POST = $_POST;
        $globals->_REQUEST = $_REQUEST;
        $globals->_FILES = $_FILES;
        $globals->_COOKIE = $_COOKIE;
        $globals->_SERVER = $_SERVER;

        $GLOBALS['globals'] = $globals;

        if ($this->_use_globals) {
            $this->globalsManager->proxy();
        }
    }

    /**
     * @param \ManaPHP\Http\Server\HandlerInterface $handler
     *
     * @return static
     */
    public function start($handler)
    {
        $this->_prepareGlobals();

        $handler->handle();

        return $this;
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

        $this->fireEvent('response:sending', ['response' => $response]);

        header('HTTP/1.1 ' . $response->status_code . ' ' . $response->status_text);

        foreach ($response->headers as $header => $value) {
            if ($value !== null) {
                header($header . ': ' . $value);
            } else {
                header($header);
            }
        }

        foreach ($response->cookies as $cookie) {
            setcookie($cookie['name'], $cookie['value'], $cookie['expire'],
                $cookie['path'], $cookie['domain'], $cookie['secure'],
                $cookie['httpOnly']);
        }

        $server = $this->request->getGlobals()->_SERVER;

        header('X-Request-Id: ' . $this->request->getRequestId());
        header('X-Response-Time: ' . sprintf('%.3f', microtime(true) - $server['REQUEST_TIME_FLOAT']));

        if ($response->status_code === 304) {
            null;
        } elseif ($server['REQUEST_METHOD'] === 'HEAD') {
            header('Content-Length: ' . strlen($response->content));
        } elseif ($response->file) {
            readfile($this->alias->resolve($response->file));
        } else {
            echo $response->content;
        }

        $this->fireEvent('response:sent', ['response' => $response]);

        return $this;
    }
}

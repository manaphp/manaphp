<?php
declare(strict_types=1);

namespace ManaPHP\Rpc;

use ManaPHP\Exception\BadRequestException;
use ManaPHP\Http\ResponseInterface;

/**
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\GlobalsInterface  $globals
 * @property-read \ManaPHP\Http\ResponseInterface $response
 * @property-read \ManaPHP\Http\RouterInterface   $router
 */
class Dispatcher extends \ManaPHP\Http\Dispatcher implements DispatcherInterface
{
    public function dispatchMessage(string $message): ?ResponseInterface
    {
        if ($message === '') {
            return null;
        } elseif ($message[0] === '{') {
            if (!is_array($json = json_parse($message)) || !isset($json['action'])) {
                throw new BadRequestException('package format is not valid');
            }

            $action = $json['action'];
            if (!preg_match('#^[a-z]\w*$#i', $action)) {
                throw new BadRequestException(['bad action: `:action`', 'action' => $action]);
            }

            if (isset($json['body'])) {
                $body = $json['body'];
            } elseif (isset($json['data'])) {
                $body = $json['data'];
            } else {
                $body = $json;
                unset($body['action']);
            }

            $globals = $this->globals->get();
            $globals->_POST = $body;
            $globals->_REQUEST = $globals->_POST + $globals->_GET;
        } elseif (preg_match('#^([a-z]\w*)[?]?#i', $message, $match) === 1) {
            $action = $match[1];
            $query = substr($message, strlen($match[0]));
            if ($query !== '') {
                parse_str($query, $body);
                if (!is_array($body)) {
                    throw new BadRequestException('invalid body');
                }

                $globals = $this->globals->get();
                $globals->_REQUEST = $globals->_GET = $body + $globals->_GET;
            }
        } else {
            return $this->response->setContent($message);
        }

        $this->router->setAction($action);

        return $this->dispatch(
            $this->router->getArea(), $this->router->getController(), $this->router->getAction(),
            $this->router->getParams()
        );
    }
}
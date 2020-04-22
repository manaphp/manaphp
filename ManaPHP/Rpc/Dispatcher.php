<?php

namespace ManaPHP\Rpc;

use ManaPHP\Exception\BadRequestException;

/**
 * Class Dispatcher
 *
 * @package ManaPHP\Rpc
 *
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\ResponseInterface $response
 * @property-read \ManaPHP\RouterInterface        $router
 * @property-read \ManaPHP\DispatcherInterface    $dispatcher
 */
class Dispatcher extends \ManaPHP\Dispatcher
{
    /**
     * @param string $message
     *
     * @return \ManaPHP\Http\ResponseInterface
     */
    public function dispatchMessage($message)
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

            $globals = $this->request->getGlobals();
            $globals->_POST = $body;
            /** @noinspection AdditionOperationOnArraysInspection */
            $globals->_REQUEST = $globals->_POST + $globals->_GET;
        } elseif (preg_match('#^([a-z]\w*)[?]?#i', $message, $match) === 1) {
            $action = $match[1];
            $query = substr($message, strlen($match[0]));
            if ($query !== '') {
                parse_str($query, $body);
                if (!is_array($body)) {
                    throw new BadRequestException('invalid body');
                }

                $globals = $this->request->getGlobals();
                /** @noinspection AdditionOperationOnArraysInspection */
                $globals->_REQUEST = $globals->_GET = $body + $globals->_GET;
            }
        } else {
            return $this->response->setContent($message);
        }

        /** @var \ManaPHP\RouterContext $routerContext */
        /** @noinspection PhpUndefinedFieldInspection */
        $routerContext = $this->router->_context;

        $routerContext->action = $action;

        return $this->dispatcher->dispatch($routerContext);
    }
}
<?php

namespace App\Middlewares;

use ManaPHP\Exception\ForbiddenException;
use ManaPHP\Http\Middleware;
use ManaPHP\Identifying\Identity\NoCredentialException;

/**
 * @property-read \ManaPHP\Http\AuthorizationInterface   $authorization
 * @property-read \ManaPHP\Identifying\IdentityInterface $identity
 * @property-read \ManaPHP\Http\RequestInterface         $request
 * @property-read \ManaPHP\Http\ResponseInterface        $response
 * @property-read \ManaPHP\Http\DispatcherInterface      $dispatcher
 */
class AuthMiddleware extends Middleware
{
    public function onAuthorize()
    {
        if ($this->authorization->isAllowed()) {
            return;
        }

        if ($this->identity->isGuest()) {
            if ($this->request->isAjax()) {
                throw new NoCredentialException('No Credential or Invalid Credential');
            } else {
                $area = $this->dispatcher->getArea();
                $redirect = input('redirect', $this->request->getUrl());
                $login_url = $area === 'Admin' ? '/admin/login' : '/user/login';
                $this->response->redirect(["$login_url?redirect=$redirect"]);
            }
        } else {
            throw new ForbiddenException('Access denied to resource');
        }
    }
}
<?php
declare(strict_types=1);

namespace App\Filters;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Exception\ForbiddenException;
use ManaPHP\Http\AuthorizationInterface;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Server\Event\RequestAuthorizing;
use ManaPHP\Identifying\Identity\NoCredentialException;
use ManaPHP\Identifying\IdentityInterface;

class AuthorizationFilter
{
    #[Autowired] protected AuthorizationInterface $authorization;
    #[Autowired] protected IdentityInterface $identity;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected DispatcherInterface $dispatcher;

    public function onAuthorizing(#[Event] RequestAuthorizing $event): void
    {
        if ($this->authorization->isAllowed($this->dispatcher->getAction())) {
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
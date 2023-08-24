<?php
declare(strict_types=1);

namespace App\Filters;

use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Exception\ForbiddenException;
use ManaPHP\Http\Server\Event\RequestAuthorizing;
use ManaPHP\Identifying\Identity\NoCredentialException;

/**
 * @property-read \ManaPHP\Http\AuthorizationInterface   $authorization
 * @property-read \ManaPHP\Identifying\IdentityInterface $identity
 * @property-read \ManaPHP\Http\RequestInterface         $request
 * @property-read \ManaPHP\Http\ResponseInterface        $response
 * @property-read \ManaPHP\Http\DispatcherInterface      $dispatcher
 */
class AuthorizationFilter
{
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
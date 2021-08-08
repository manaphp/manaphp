<?php

namespace App;

use ManaPHP\Identifying\Identity\NoCredentialException;
use ManaPHP\Exception\ForbiddenException;

/**
 * @property \ManaPHP\Http\AuthorizationInterface   $authorization
 * @property \ManaPHP\Identifying\IdentityInterface $identity
 */
class Application extends \ManaPHP\Mvc\Application
{
    public function authorize()
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

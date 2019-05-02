<?php
namespace App;

use ManaPHP\Exception\ForbiddenException;

class Application extends \ManaPHP\Mvc\Application
{
    public function authenticate()
    {
        $this->identity->authenticate();
    }

    public function authorize()
    {
        if ($this->request->isAjax()) {
            $this->authorization->authorize();
        } else {
            if (!$this->authorization->isAllowed()) {
                if ($this->identity->isGuest()) {
                    $redirect = input('redirect', $this->request->getUrl());
                    $this->response->redirect(["/login?redirect=$redirect"]);
                } else {
                    throw new ForbiddenException('');
                }
            }
        }
    }
}

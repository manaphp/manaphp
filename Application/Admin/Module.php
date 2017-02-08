<?php
namespace Application\Admin;

use ManaPHP\Authentication\UserIdentity;
use ManaPHP\Authorization\Rbac;
use ManaPHP\I18n\Translation;

class Module extends \ManaPHP\Mvc\Module
{
    public function registerServices($di)
    {
        $this->_dependencyInjector->authorization = new Rbac();
        $this->_dependencyInjector->userIdentity = new UserIdentity($this->session->get('admin_auth', []));
        $this->_dependencyInjector->translation = function () {
            return new Translation(['language' => 'zh-CN,en']);
        };
    }

    public function authorize($controller, $action)
    {
        if (!in_array($controller . ':' . $action, ['User:captcha', 'User:login', 'User:register'], true) && !$this->userIdentity->getId()) {
            return $this->response->redirect(['/user/login?redirect=' . $this->request->get('redirect', null, $this->request->getUrl(true))]);
        }
    }
}
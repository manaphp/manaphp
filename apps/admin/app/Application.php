<?php
namespace App;

class Application extends \ManaPHP\Mvc\Application
{
    /**
     * @var string
     */
    protected $_loginUrl = '/user/session/login';

    public function authenticate()
    {
        $this->identity->authenticate();
    }

    public function authorize()
    {
        try {
            $this->authorization->authorize();
        } catch (\Exception $exception) {
            if ($this->request->isAjax()) {
                return $this->response->setJsonContent($exception);
            } else {
                $redirect = input('redirect', $this->request->getUrl());
                $sep = (strpos($this->_loginUrl, '?') ? '&' : '?');
                return $this->response->redirect(["{$this->_loginUrl}{$sep}redirect=$redirect"]);
            }
        }
    }
}

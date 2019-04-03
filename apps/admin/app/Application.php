<?php
namespace App;

class Application extends \ManaPHP\Mvc\Application
{
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
                return $this->response->redirect(["/login?redirect=$redirect"]);
            }
        }
    }
}

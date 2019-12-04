<?php
namespace App\Areas\Admin\Controllers;

use App\Models\Admin;
use ManaPHP\Mvc\Controller;

class AccountController extends Controller
{
    public function getAcl()
    {
        return ['*' => 'user'];
    }

    public function captchaAction()
    {
        return $this->captcha->generate();
    }

    public function registerAction()
    {
        if ($this->request->isAjax()) {
            $this->captcha->verify();
            return Admin::rCreate();
        }
    }
}
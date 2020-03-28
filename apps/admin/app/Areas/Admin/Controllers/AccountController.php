<?php
namespace App\Areas\Admin\Controllers;

use App\Models\Admin;
use ManaPHP\Mvc\Controller;

class AccountController extends Controller
{
    public function getAcl()
    {
        return ['*' => 'admin'];
    }

    public function captchaAction()
    {
        return $this->captcha->generate();
    }

    public function registerAction()
    {
        $this->captcha->verify();
        return Admin::rCreate(['admin_name', 'email', 'password', 'white_ip' => '*', 'status' => Admin::STATUS_INIT]);
    }
}
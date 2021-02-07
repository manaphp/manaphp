<?php

namespace App\Areas\Admin\Controllers;

use App\Controllers\Controller;
use App\Models\Admin;

/**
 * @property-read \ManaPHP\Http\CaptchaInterface $captcha
 */
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
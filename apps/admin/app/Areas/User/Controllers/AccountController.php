<?php
namespace App\Areas\User\Controllers;

use App\Models\Admin;
use ManaPHP\Mvc\Controller;

class AccountController extends Controller
{
    public function registerAction()
    {
        if ($this->request->isAjax()) {
            $this->captcha->verify();

            $admin = Admin::newOrFail();

            $admin->status = Admin::STATUS_ACTIVE;
            $admin->login_ip = '';
            $admin->login_time = 0;
            $admin->salt = $this->password->salt();
            $admin->password = $this->password->hash($admin->password, $admin->salt);

            $admin->create();

            return 0;
        }
    }
}
<?php

namespace App\Areas\Admin\Controllers;

use App\Controllers\Controller;

class MailController extends Controller
{
    public function getAcl()
    {
        return ['*' => 'admin'];
    }

    public function resetPasswordAction()
    {
        $this->view->setVars(
            [
                'admin_name' => 'manaphp',
                'token'      => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX25hbWUiOiJhZG1pbiIsInNjb3BlIjoiYWRtaW4udXNlci5wYXNzd29yZC5mb3JnZXQiLCJpYXQiOjE1NTQzNzM0MDgsImV4cCI6MTU1NDM3MzcwOH0.aMlRI9SAnW_6L4Qo89cmYF1kOyGZXmiPWj_3cNiRA9g',
                'email'      => 'test@qq.com',
            ]
        );
    }
}
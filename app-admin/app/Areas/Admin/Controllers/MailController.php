<?php
declare(strict_types=1);

namespace App\Areas\Admin\Controllers;

use App\Controllers\Controller;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\PostMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;

#[Authorize]
#[RequestMapping('/admin/mail')]
class MailController extends Controller
{
    #[ViewGetMapping, PostMapping]
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
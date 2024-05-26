<?php
declare(strict_types=1);

namespace App\Areas\Admin\Controllers;

use App\Controllers\Controller;
use App\Entities\Admin;
use App\Repositories\AdminRepository;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\CaptchaInterface;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Router\Attribute\PostMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;

#[Authorize(Authorize::ADMIN)]
#[RequestMapping('/admin/account')]
class AccountController extends Controller
{
    #[Autowired] protected CaptchaInterface $captcha;
    #[Autowired] protected AdminRepository $adminRepository;

    #[PostMapping]
    public function captchaAction(): ResponseInterface
    {
        return $this->captcha->generate();
    }

    #[ViewGetMapping, PostMapping]
    public function registerAction(string $code, string $password)
    {
        $this->captcha->verify($code);

        $admin = $this->adminRepository->fill($this->request->all());

        $admin->white_ip = '*';
        $admin->status = Admin::STATUS_INIT;
        $admin->password = $password;

        return $this->adminRepository->create($admin);
    }
}
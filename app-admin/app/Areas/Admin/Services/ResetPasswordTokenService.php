<?php
declare(strict_types=1);

namespace App\Areas\Admin\Services;

use App\Models\Admin;
use App\Services\Service;
use Exception;

class ResetPasswordTokenService extends Service
{
    public function generate(string $admin_name): string
    {
        $admin = Admin::firstOrFail(['admin_name' => $admin_name]);
        return jwt_encode(['admin_name' => $admin->admin_name], 1800, 'admin.reset_password');
    }

    public function verify(string $token): bool|array
    {
        try {
            return jwt_decode($token, 'admin.reset_password');
        } catch (Exception $exception) {
            return false;
        }
    }
}

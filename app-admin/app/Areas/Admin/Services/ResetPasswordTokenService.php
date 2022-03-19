<?php
declare(strict_types=1);

namespace App\Areas\Admin\Services;

use App\Models\Admin;
use App\Services\Service;

class ResetPasswordTokenService extends Service
{
    /**
     * @param $admin_name
     *
     * @return string
     */
    public function generate($admin_name)
    {
        $admin = Admin::firstOrFail(['admin_name' => $admin_name]);
        return jwt_encode(['admin_name' => $admin->admin_name], 1800, 'admin.reset_password');
    }

    /**
     * @param string $token
     *
     * @return bool|array
     */
    public function verify($token)
    {
        try {
            return jwt_decode($token, 'admin.reset_password');
        } catch (\Exception $exception) {
            return false;
        }
    }
}

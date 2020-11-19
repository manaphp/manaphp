<?php

namespace App\Areas\Admin\Services;

use App\Models\Admin;
use App\Services\Service;
use ManaPHP\Token\Jwt;

class ResetPasswordTokenService extends Service
{
    const KEY_SCOPE = 'admin:user:account:reset_password';
    const TTL = 1800;

    /**
     * @var Jwt
     */
    protected $_jwt;

    public function __construct()
    {
        $this->_jwt = new Jwt(['key' => $this->crypt->getDerivedKey(self::KEY_SCOPE), 'ttl' => self::TTL]);
    }

    /**
     * @param $admin_name
     *
     * @return string
     */
    public function generate($admin_name)
    {
        $admin = Admin::firstOrFail(['admin_name' => $admin_name]);
        $data = ['admin_name' => $admin->admin_name];
        return $this->_jwt->encode($data);
    }

    /**
     * @param string $token
     *
     * @return bool|array
     */
    public function verify($token)
    {
        try {
            return $this->_jwt->decode($token);
        } catch (\Exception $exception) {
            return false;
        }
    }
}

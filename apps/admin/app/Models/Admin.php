<?php

namespace App\Models;

use App\Areas\Rbac\Models\AdminRole;
use App\Areas\Rbac\Models\Role;

class Admin extends Model
{
    const STATUS_INIT = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_LOCKED = 2;

    const PASSWORD_LENGTH = '1-30';

    public $admin_id;
    public $admin_name;
    public $email;
    public $status;
    public $salt;
    public $password;
    public $white_ip;
    public $login_ip;
    public $login_time;
    public $session_id;
    public $creator_name;
    public $updator_name;
    public $created_time;
    public $updated_time;

    /**
     * @return string
     */
    public function getTable()
    {
        return 'admin';
    }

    public function rules()
    {
        return [
            'admin_name' => ['length' => '4-16', 'account', 'readonly'],
            'email'      => ['lower', 'email', 'unique'],
            'status'     => 'const',
            'white_ip'   => ['default' => '', 'maxLength' => 64]
        ];
    }

    public function relations()
    {
        return ['roles' => $this->hasManyToMany(Role::class, AdminRole::class)];
    }

    /**
     * @param string $password
     *
     * @return string
     */
    public function hashPassword($password)
    {
        return md5(md5($password) . $this->salt);
    }

    /**
     * @param string $password
     *
     * @return bool
     */
    public function verifyPassword($password)
    {
        return $this->hashPassword($password) === $this->password;
    }

    public function create()
    {
        $this->salt = bin2hex(random_bytes(8));

        $this->password = $this->hashPassword(input('password', ['string', self::PASSWORD_LENGTH]));

        return parent::create();
    }

    public function update()
    {
        if ($this->hasChanged(['password'])) {
            $this->salt = bin2hex(random_bytes(8));
            $this->password = $this->hashPassword($this->password);
        }

        return parent::update();
    }
}
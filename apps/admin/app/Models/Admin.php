<?php
namespace App\Models;

/**
 * Class Admin
 *
 * @package App\Models
 */
class Admin extends ModelBase
{
    const STATUS_INIT = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_LOCKED = 2;

    public $admin_id;
    public $admin_name;
    public $email;
    public $status;
    public $salt;
    public $password;
    public $login_ip;
    public $login_time;
    public $creator_name;
    public $updator_name;
    public $created_time;
    public $updated_time;

    public function getSource($context = null)
    {
        return 'admin';
    }

    public function rules()
    {
        return [
            'admin_name' => ['lower', 'length' => '5-16', 'unique'],
            'email' => ['lower', 'email', 'unique'],
            'password' => ['length' => '6-32'],
            'status' => 'const'
        ];
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
        $this->password = $this->hashPassword($this->password);

        return parent::create();
    }

    public function update()
    {
        if ($this->hasChanged('password')) {
            $this->salt = bin2hex(random_bytes(8));
            $this->password = $this->hashPassword($this->password);
        }

        return parent::update();
    }
}
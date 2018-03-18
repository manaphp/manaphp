<?php
namespace App\Admin\Models;

use App\Admin\Rbac\Models\UserRole;

/**
 * Class Admin
 *
 * @package App\Admin\Models
 */
class Admin extends ModelBase
{
    const STATUS_INIT = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_LOCKED = 2;

    /**
     * @var int
     */
    public $admin_id;

    /**
     * @var string
     */
    public $admin_name;

    /**
     * @var string
     */
    public $email;

    /**
     * @var int
     */
    public $status;

    /**
     * @var string
     */
    public $salt;

    /**
     * @var string
     */
    public $password;

    /**
     * @var int
     */
    public $login_time;

    /**
     * @var string
     */
    public $login_ip;

    /**
     * @var int
     */
    public $creator_id;

    /**
     * @var string
     */
    public $creator_name;

    /**
     * @var int
     */
    public $created_time;

    /**
     * @var int
     */
    public $updated_time;

    public function getSource($context = null)
    {
        return 'admin';
    }

    /**
     * @return \ManaPHP\Model\CriteriaInterface
     */
    public function getRoles()
    {
        return $this->hasMany(UserRole::class, ['user_id' => 'admin_id']);
    }
}
<?php
namespace ManaPHP\Authorization\Rbac\Models;

use ManaPHP\Mvc\Model;

/**
 * Class ManaPHP\Authorization\Rbac\Models\UserRole
 *
 * @package ManaPHP\Authorization\Rbac\Models
 */
class UserRole extends Model
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $user_id;

    /**
     * @var int
     */
    public $role_id;

    /**
     * @var int
     */
    public $created_time;

    /**
     * @return string
     */
    public function getSource()
    {
        return 'rbac_user_role';
    }
}
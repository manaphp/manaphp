<?php
namespace ManaPHP\Authorization\Rbac\Models;

use ManaPHP\Mvc\Model;

/**
 * Class ManaPHP\Authorization\Rbac\Models\UserRole
 *
 * @package rbac\models
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
    
    public function getSource($context = null)
    {
        return 'rbac_user_role';
    }
}
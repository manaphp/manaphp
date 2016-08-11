<?php
namespace ManaPHP\Authorization\Rbac\Models;

use ManaPHP\Mvc\Model;

class UserRole extends Model
{
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
}
<?php
namespace ManaPHP\Authorization\Rbac\Models;

use ManaPHP\Mvc\Model;

class Role extends Model
{
    /**
     * @var int
     */
    public $role_id;

    /**
     * @var string
     */
    public $role_name;

    /**
     * @var int
     */
    public $created_time;
}
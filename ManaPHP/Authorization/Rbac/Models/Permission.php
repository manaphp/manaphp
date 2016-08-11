<?php
namespace ManaPHP\Authorization\Rbac\Models;

use ManaPHP\Mvc\Model;

class Permission extends Model
{
    const TYPE_PENDING = 0;
    const TYPE_PUBLIC = 1;
    const TYPE_INTERNAL = 2;
    const TYPE_PRIVATE = 3;

    /**
     * @var int
     */
    public $permission_id;

    /**
     * @var string
     */
    public $permission_name;

    /**
     * @var int
     */
    public $permission_type;

    /**
     * @var int
     */
    public $created_time;
}
<?php
namespace ManaPHP\Authorization\Rbac\Models;

use ManaPHP\Mvc\Model;

/**
 * Class ManaPHP\Authorization\Rbac\Models\Role
 *
 * @package rbac\models
 */
class Role extends Model
{
    /**
     * @var int
     */
    public $role_id;

    /**
     * @var int
     */
    public $enabled;

    /**
     * @var string
     */
    public $role_name;

    /**
     * @var string
     */
    public $description;

    /**
     * @var int
     */
    public $created_time;

    public static function getSource($context = null)
    {
        return 'rbac_role';
    }
}
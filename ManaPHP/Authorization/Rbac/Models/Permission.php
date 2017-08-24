<?php
namespace ManaPHP\Authorization\Rbac\Models;

use ManaPHP\Db\Model;

/**
 * Class ManaPHP\Authorization\Rbac\Models\Permission
 *
 * @package rbac\models
 */
class Permission extends Model
{
    const TYPE_PENDING = 0;
    const TYPE_PUBLIC = 1;
    const TYPE_INTERNAL = 2;
    const TYPE_PRIVATE = 3;
    const TYPE_DISABLED = 4;

    /**
     * @var int
     */
    public $permission_id;

    /**
     * @var int
     */
    public $permission_type;

    /**
     * @var string
     */
    public $module;

    /**
     * @var string
     */
    public $controller;

    /**
     * @var string
     */
    public $action;

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
        return 'rbac_permission';
    }
}
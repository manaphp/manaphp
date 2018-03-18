<?php
namespace ManaPHP\Authorization\Rbac\Models;

use ManaPHP\Db\Model;

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
     * @var string
     */
    public $role_name;

    /**
     * @var int
     */
    public $enabled;

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
        return 'rbac_role';
    }
}
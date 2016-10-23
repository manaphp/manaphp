<?php
namespace ManaPHP\Authorization\Rbac\Models;

use ManaPHP\Mvc\Model;

/**
 * Class ManaPHP\Authorization\Rbac\Models\Role
 *
 * @package ManaPHP\Authorization\Rbac\Models
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
     * @var string
     */
    public $description;

    /**
     * @var int
     */
    public $created_time;

    /**
     * @return string
     */
    public function getSource()
    {
        return 'rbac_role';
    }
}
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
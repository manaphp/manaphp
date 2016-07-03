<?php
namespace ManaPHP\Authorization\Rbac\Models {

    use ManaPHP\Mvc\Model;

    class RolePermission extends Model
    {
        /**
         * @var int
         */
        public $role_id;

        /**
         * @var int
         */
        public $permission_id;

        /**
         * @var int
         */
        public $created_time;
    }
}

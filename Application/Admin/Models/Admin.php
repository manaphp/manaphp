<?php
namespace Application\Admin\Models;

/**
 * Class Admin
 *
 * @package Application\Admin\Models
 *
 * @method static $this|false findFirstByAdminName(string $admin_name, int|array $cacheOptions = null)
 */
class Admin extends ModelBase
{
    /**
     * @var int
     */
    public $admin_id;

    /**
     * @var string
     */
    public $admin_name;

    /**
     * @var string
     */
    public $salt;

    /**
     * @var string
     */
    public $password;

    /**
     * @var int
     */
    public $created_time;

    /**
     * @var int
     */
    public $updated_time;
}
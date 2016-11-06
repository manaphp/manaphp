<?php
namespace Application\Admin\Models;

class AdminDetail extends ModelBase
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
    public $email;

    /**
     * @var int
     */
    public $created_time;

    /**
     * @var int
     */
    public $updated_time;
}
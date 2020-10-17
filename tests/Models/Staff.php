<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/28
 * Time: 0:05
 */

namespace Tests\Models;

use ManaPHP\Db\Model;

class Staff extends Model
{
    public $staff_id;
    public $first_name;
    public $last_name;
    public $address_id;
    public $picture;
    public $email;
    public $store_id;
    public $active;
    public $username;
    public $password;
    public $last_update;
}
<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/28
 * Time: 0:05
 */
namespace Models;

use ManaPHP\Mvc\Model;

class Store extends Model
{
    public $store_id;
    public $manager_staff_id;
    public $address_id;
    public $last_update;

    public function getSource()
    {
        return 'store';
    }
}
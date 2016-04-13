<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/28
 * Time: 0:04
 */
namespace Models;

use ManaPHP\Mvc\Model;

class Rental extends Model
{
    public $rental_id;
    public $rental_date;
    public $inventory_id;
    public $customer_id;
    public $return_date;
    public $staff_id;
    public $last_update;

    public function getSource()
    {
        return 'rental';
    }
}
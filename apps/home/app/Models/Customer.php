<?php
namespace App\Models;

/**
 * Class Customer
 */
class Customer extends \ManaPHP\Db\Model
{
    public $customer_id;
    public $store_id;
    public $first_name;
    public $last_name;
    public $email;
    public $address_id;
    public $active;
    public $create_date;
    public $last_update;
}
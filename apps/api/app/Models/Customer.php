<?php
namespace App\Models;

/**
 * Class App\Models\Customer
 *
 * @package App\Models
 *
 * @property \App\Models\Address $address
 */
class Customer extends ModelBase
{
    public $customer_id;
    public $first_name;
    public $last_name;
    public $address_id;
}
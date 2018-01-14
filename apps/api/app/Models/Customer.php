<?php
namespace App\Api\Models;

/**
 * Class App\Api\Models\Customer
 *
 * @package App\Api\Models
 *
 * @property \App\Api\Models\Address $address
 */
class Customer extends ModelBase
{
    public $customer_id;
    public $first_name;
    public $last_name;
    public $address_id;
}
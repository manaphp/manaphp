<?php

namespace App\Models;

/**
 * Class Address
 *
 * @property-read \App\Models\Customer $customers
 */
class Address extends \ManaPHP\Db\Model
{
    public $address_id;
    public $address;
    public $address2;
    public $district;
    public $city_id;
    public $postal_code;
    public $phone;
    public $last_update;
}
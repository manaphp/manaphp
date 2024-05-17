<?php
declare(strict_types=1);

namespace App\Entities;

/**
 * @property-read \App\Entities\Customer $customers
 */
class Address extends Entity
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
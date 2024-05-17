<?php
declare(strict_types=1);

namespace App\Entities;

/**
 * @property-read \App\Entities\Customer $customers
 */
class Address extends Entity
{
    public int $address_id;
    public string $address;
    public string $address2;
    public string $district;
    public int $city_id;
    public string $postal_code;
    public string $phone;
    public string $last_update;
}
<?php
declare(strict_types=1);

namespace App\Models;

/**
 * @property-read \App\Models\Customer $customers
 */
class Address extends Model
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
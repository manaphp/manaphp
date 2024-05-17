<?php
declare(strict_types=1);

namespace App\Entities;

/**
 * @property-read City $cities
 */
class Country extends Entity
{
    public $country_id;
    public $country;
    public $last_update;
}
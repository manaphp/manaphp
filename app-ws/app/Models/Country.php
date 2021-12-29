<?php
declare(strict_types=1);

namespace App\Models;

/**
 * @property-read City $cities
 */
class Country extends Model
{
    public $country_id;
    public $country;
    public $last_update;
}
<?php
declare(strict_types=1);

namespace App\Models;

/**
 * @property Country $country
 */
class City extends Model
{
    public $city_id;
    public $city;
    public $country_id;
    public $last_update;
}
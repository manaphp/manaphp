<?php

namespace App\Models;

/**
 * @property-read Country $country
 */
class City extends Model
{
    public $city_id;
    public $city;
    public $country_id;
    public $last_update;
}
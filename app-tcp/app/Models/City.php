<?php

namespace App\Models;

/**
 * @property \App\Models\Country $country
 */
class City extends Model
{
    public $city_id;
    public $city;
    public $country_id;
    public $last_update;
}
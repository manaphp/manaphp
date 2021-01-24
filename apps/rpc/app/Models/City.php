<?php

namespace App\Models;

/**
 * Class City
 *
 * @property-read Country $country
 */
class City extends Model
{
    public $city_id;
    public $city;
    public $country_id;
    public $last_update;

    /**
     * @return string
     */
    public function getTable()
    {
        return 'city';
    }

    /**
     * @return string
     */
    public function primaryKey()
    {
        return 'city_id';
    }
}
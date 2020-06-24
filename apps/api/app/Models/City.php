<?php

namespace App\Models;

/**
 * Class City
 *
 * @property \App\Models\Country $country
 */
class City extends \ManaPHP\Db\Model
{
    public $city_id;
    public $city;
    public $country_id;
    public $last_update;
}
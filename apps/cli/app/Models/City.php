<?php

namespace App\Models;

/**
 * Class City
 *
 * @property \App\Models\Country $country
 */
class City extends \ManaPHP\Data\Db\Model
{
    public $city_id;
    public $city;
    public $country_id;
    public $last_update;
}
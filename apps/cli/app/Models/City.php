<?php
namespace App\Cli\Models;

use ManaPHP\Db\Model;

/**
 * Class App\Home\Models\City
 *
 * @package App\Home\Models
 * @property \App\Home\Models\Country $country
 */
class City extends Model
{
    public $city_id;
    public $city;
    public $country_id;
    public $last_update;
}
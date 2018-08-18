<?php
namespace App\Models;

use ManaPHP\Db\Model;
use ManaPHP\Model\Relation;

/**
 * Class App\Models\City
 *
 * @package App\Models
 * @property \App\Models\Country $country
 */
class City extends Model
{
    public $city_id;
    public $city;
    public $country_id;
    public $last_update;
    public $country;

    public function relations()
    {
        return [
            'country' => [Country::class, Relation::TYPE_HAS_ONE]
        ];
    }
}
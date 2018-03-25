<?php
namespace App\Cli\Models;

use ManaPHP\Db\Model;
use ManaPHP\Model\Relation;

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
    public $country;


    public function relations()
    {
        return [
            'cousntry' => [Relation::TYPE_HAS_ONE, Country::class]
        ];
    }
}
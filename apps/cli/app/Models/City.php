<?php
namespace App\Models;

/**
 * Class City
 * @property \App\Models\Country $country
 */
class City extends \ManaPHP\Db\Model
{
    public $city_id;
    public $city;
    public $country_id;
    public $last_update;

    public static function sample()
    {
        return [
            'city_id' => 1,
            'city' => 'beijing',
            'country_id' => 1,
            'last_update' => '2019-01-02 11:12:13'
        ];
    }
}
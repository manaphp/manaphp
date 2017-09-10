<?php
namespace Mongodb\Models;

class City extends \ManaPHP\Mongodb\Model
{
    public $city_id;
    public $city;
    public $country_id;
    public $last_update;

    public static function getFieldTypes()
    {
        return [
            '_id' => 'integer',
            'city_id' => 'integer',
            'city' => 'string',
            'country_id' => 'integer',
            'last_update' => 'string'
        ];
    }
}
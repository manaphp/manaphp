<?php
namespace Application\Home\Models;

use ManaPHP\Mongodb\Model;

class CityM extends Model
{
    public $city_id;
    public $city;
    public $country_id;
    public $last_update;

    public static function getSource($context = null)
    {
        return 'city';
    }

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

    public static function getAutoIncrementField()
    {
        return 'city_id';
    }
}
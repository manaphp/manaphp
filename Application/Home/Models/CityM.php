<?php
namespace Application\Home\Models;

use ManaPHP\Mongodb\Model;

class CityM extends Model
{
    public $_id;
    public $id;
    public $city_id;
    public $city;
    public $country_id;
    public $last_update;

    public static function getSource($context = null)
    {
        return 'city';
    }

    public static function getFieldType($field)
    {
        $types = ['_id' => 'objectid', 'country_id' => 'integer', 'city_id' => 'integer'];
        if (isset($types[$field])) {
            return $types[$field];
        }
        return parent::getFieldType($field);
    }
}
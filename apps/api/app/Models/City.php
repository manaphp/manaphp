<?php
namespace App\Models;

/**
 * Class City
 */
class City extends \ManaPHP\Db\Model
{
    public $city_id;
    public $city;
    public $country_id;
    public $last_update;

    public function getDisplayField()
    {
        return 'city';
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/28
 * Time: 0:01
 */
namespace Models;

use ManaPHP\Mvc\Model;

class City extends Model
{
    public $city_id;
    public $city;
    public $country_id;
    public $last_update;

    public function getSource()
    {
        return 'city';
    }
}
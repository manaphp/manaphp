<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/28
 * Time: 0:00
 */
namespace Models;

use ManaPHP\Mvc\Model;

class Address extends Model
{
    public $address_id;
    public $address;
    public $address2;
    public $district;
    public $city_id;
    public $postal_code;
    public $phone;
    public $last_update;

    public function getSource()
    {
        return 'address';
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: MarkMa
 * Date: 2016/3/25
 */

namespace Application\Home\Models;

class City extends ModelBase
{
    public $city_id;
    public $city;
    public $country_id;
    public $last_update;

    public function initialize()
    {
        $this->setSource('city');
    }
}
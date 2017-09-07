<?php
/**
 * Created by PhpStorm.
 * User: MarkMa
 * Date: 2016/3/25
 */

namespace Application\Home\Models;

/**
 * Class Application\Home\Models\City
 *
 * @package Application\Home\Models
 * @property \Application\Home\Models\Country $country
 */
class City extends ModelBase
{
    public $city_id;
    public $city;
    public $country_id;
    public $last_update;

    /**
     * @return \ManaPHP\Model\CriteriaInterface
     */
    public function getCountry()
    {
        return $this->hasOne(Country::class);
    }
}
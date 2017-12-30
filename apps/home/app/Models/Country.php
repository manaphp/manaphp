<?php
namespace App\Home\Models;

/**
 * Class App\Home\Models\Country
 *
 * @package App\Home\Models
 *
 * @property \App\Home\Models\City $cities
 */
class Country extends ModelBase
{
    public $country_id;
    public $country;
    public $last_update;

    /**
     * @return \ManaPHP\Model\CriteriaInterface
     */
    public function getCities()
    {
        return $this->hasMany(City::class);
    }
}
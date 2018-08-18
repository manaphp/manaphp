<?php
namespace App\Models;

/**
 * Class App\Models\Country
 *
 * @package App\Models
 *
 * @property \App\Models\City $cities
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
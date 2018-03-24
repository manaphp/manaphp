<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/28
 * Time: 0:01
 */
namespace Tests\Models;

use ManaPHP\Mvc\Model;

/**
 * Class Country
 * @package Tests\Models
 * @property \Tests\Models\City[] $cities
 * @property \Tests\Models\City[] $citiesExplicit
 * @method  \ManaPHP\Model\Criteria getCities
 */
class Country extends Model
{
    public $country_id;
    public $country;
    public $last_update;

    public function getCitiesExplicit()
    {
        return $this->hasMany(City::class);
    }
}
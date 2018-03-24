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
 * Class City
 * @package Tests\Models
 * @property \Tests\Models\Country|false $country
 * @property \Tests\Models\Country|false $countryExplicit
 * @method \ManaPHP\Model\Criteria getCountry
 */
class City extends Model
{
    public $city_id;
    public $city;
    public $country_id;
    public $last_update;

    public function rules()
    {
        return [
            'city' => ['required', 'unique'],
            'city_id' => 'int',
            'country_id' => 'exists',
            'last_update' => 'date'
        ];
    }

    /**
     * @return false|\ManaPHP\Model\Criteria
     */
    public function getCountryExplicit()
    {
        return $this->hasOne(Country::class);
    }

    public function getDisplayField()
    {
        return 'city';
    }
}
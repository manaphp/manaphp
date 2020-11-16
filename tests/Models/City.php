<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/28
 * Time: 0:01
 */

namespace Tests\Models;

use ManaPHP\Data\Db\Model;
use ManaPHP\Data\Relation;

/**
 * Class City
 *
 * @package Tests\Models
 * @property \Tests\Models\Country|false $country
 * @property \Tests\Models\Country|false $countryExplicit
 * @method \ManaPHP\Data\Query getCountry
 * @method \ManaPHP\Data\Query getCountryExplicit
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
            'city'        => ['required', 'unique'],
            'city_id'     => 'int',
            'country_id'  => 'exists',
            'last_update' => 'date'
        ];
    }

    public function relations()
    {
        return ['countryExplicit' => [Country::class, Relation::TYPE_HAS_ONE]];
    }
}
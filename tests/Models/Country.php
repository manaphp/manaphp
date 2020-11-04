<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/28
 * Time: 0:01
 */

namespace Tests\Models;

use ManaPHP\Db\Model;
use ManaPHP\Model\Relation;

/**
 * Class Country
 *
 * @package Tests\Models
 * @property \Tests\Models\City[] $cities
 * @property \Tests\Models\City[] $citiesExplicit
 * @method  \ManaPHP\Query getCities
 * @method  \ManaPHP\Query getCitiesExplicit
 */
class Country extends Model
{
    public $country_id;
    public $country;
    public $last_update;

    public function relations()
    {
        return ['citiesExplicit' => [City::class, Relation::TYPE_HAS_MANY]];
    }
}
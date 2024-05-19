<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/28
 * Time: 0:01
 */

namespace Tests\Models;

use ManaPHP\Data\AbstractRelation;
use ManaPHP\Data\Db\Model;

/**
 * Class Country
 *
 * @package Tests\Models
 * @property \Tests\Models\City[] $cities
 * @property \Tests\Models\City[] $citiesExplicit
 * @method  \ManaPHP\Data\QueryInterface getCities
 * @method  \ManaPHP\Data\QueryInterface getCitiesExplicit
 */
class Country extends Model
{
    public $country_id;
    public $country;
    public $last_update;
}
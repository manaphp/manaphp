<?php
namespace App\Models;

use ManaPHP\Db\Model;
use ManaPHP\Model\Relation;

/**
 * Class Country
 * @package App\Models
 *
 * @property \App\Models\City[] $cities
 */
class Country extends Model
{
    public $country_id;
    public $country;
    public $last_update;

    public function relations()
    {
        return ['cities' => [City::class, Relation::TYPE_HAS_MANY]];
    }
}
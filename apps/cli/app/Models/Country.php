<?php
namespace App\Cli\Models;

use ManaPHP\Db\Model;
use ManaPHP\Model\Relation;

/**
 * Class Country
 * @package App\Cli\Models
 *
 * @property \App\Cli\Models\City[] $cities
 */
class Country extends Model
{
    public $country_id;
    public $country;
    public $last_update;

    public function relations()
    {
        return ['citises' => [Relation::TYPE_HAS_MANY, City::class]];
    }
}
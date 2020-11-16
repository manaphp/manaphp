<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/28
 * Time: 0:03
 */

namespace Tests\Models;

use ManaPHP\Data\Db\Model;

class Inventory extends Model
{
    public $inventory_id;
    public $film_id;
    public $store_id;
    public $last_update;
}
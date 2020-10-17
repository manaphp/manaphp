<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/28
 * Time: 0:04
 */

namespace Tests\Models;

use ManaPHP\Db\Model;

class Language extends Model
{
    public $language_id;
    public $name;
    public $last_update;
}
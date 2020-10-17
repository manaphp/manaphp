<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/28
 * Time: 0:02
 */

namespace Tests\Models;

use ManaPHP\Db\Model;

class FilmActor extends Model
{
    public $actor_id;
    public $film_id;
    public $last_update;
}
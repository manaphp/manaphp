<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/28
 * Time: 0:03
 */

namespace Tests\Models;

use ManaPHP\Data\Db\Model;

class FilmText extends Model
{
    public $film_id;
    public $title;
    public $description;
}
<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/28
 * Time: 0:03
 */
namespace Models;

use ManaPHP\Mvc\Model;

class FilmCategory extends Model
{
    public $film_id;
    public $category_id;
    public $last_update;

    public function getSource()
    {
        return 'film_category';
    }
}
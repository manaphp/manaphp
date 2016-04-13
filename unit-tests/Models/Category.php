<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/28
 * Time: 0:00
 */
namespace Models;

use ManaPHP\Mvc\Model;

class Category extends Model
{
    public $category_id;
    public $name;
    public $last_update;

    public function getSource()
    {
        return 'category';
    }
}
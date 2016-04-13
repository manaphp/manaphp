<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/27
 * Time: 23:58
 */
namespace Models;

use ManaPHP\Mvc\Model;

class Actor extends Model
{
    public $actor_id;
    public $first_name;
    public $last_name;
    public $last_update;

    public function getSource()
    {
        return 'actor';
    }
}
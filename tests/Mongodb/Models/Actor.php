<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/27
 * Time: 23:58
 */

namespace Tests\Mongodb\Models;

use ManaPHP\Data\Mongodb\Model;

/**
 * Class Actor
 *
 * @package Models
 */
class Actor extends Model
{
    public $actor_id;
    public $first_name;
    public $last_name;
    public $last_update;

    public function fieldTypes()
    {
        return [
            '_id'         => 'integer',
            'actor_id'    => 'integer',
            'first_name'  => 'string',
            'last_name'   => 'string',
            'last_update' => 'string'
        ];
    }
}
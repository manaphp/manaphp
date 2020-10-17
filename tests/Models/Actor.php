<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/27
 * Time: 23:58
 */

namespace Tests\Models;

use ManaPHP\Db\Model;

/**
 * Class Actor
 *
 * @package Models
 * @method  static $this findFirstByActorId(int $actor_id, int | array $cacheOptions = null)
 * @method static Actor[] findByFirstName(string $first_name, int | array $cacheOptions = null)
 * @method static int countByFirstName(string $first_name, int | array $cacheOptions = null)
 */
class Actor extends Model
{
    public $actor_id;
    public $first_name;
    public $last_name;
    public $last_update;
}
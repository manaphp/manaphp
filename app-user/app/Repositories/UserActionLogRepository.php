<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Events\UserActionLog;
use ManaPHP\Db\Repository;

/**
 * @extends Repository<UserActionLog>
 */
class UserActionLogRepository extends Repository
{

}
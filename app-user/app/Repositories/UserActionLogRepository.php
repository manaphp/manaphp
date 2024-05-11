<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Events\UserActionLog;
use ManaPHP\Persistence\Repository;

/**
 * @extends Repository<UserActionLog>
 */
class UserActionLogRepository extends Repository
{

}
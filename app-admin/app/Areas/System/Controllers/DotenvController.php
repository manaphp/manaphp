<?php
declare(strict_types=1);

namespace App\Areas\System\Controllers;

use App\Areas\System\Models\DotenvLog;
use App\Areas\System\Repositories\DotenvLogRepository;
use App\Controllers\Controller;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Http\Router\Attribute\GetMapping;
use ManaPHP\Http\Router\Attribute\PostMapping;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Mvc\View\Attribute\ViewGetMapping;
use ManaPHP\Redis\RedisDbInterface;

#[Authorize('@index')]
#[RequestMapping('/system')]
class DotenvController extends Controller
{
    #[Autowired] protected RedisDbInterface $redisDb;
    #[Autowired] protected DotenvLogRepository $dotenvLogRepository;

    public const REDIS_KEY = '.env';

    #[ViewGetMapping('')]
    public function indexAction(string $app_id = '')
    {
        if ($app_id === '') {
            return [];
        } else {
            $current = [['app_id' => $app_id, 'env' => $this->redisDb->hGet(self::REDIS_KEY, $app_id) ?: '']];
            $logs = DotenvLog::where(['app_id' => $app_id])->orderBy(['id' => SORT_DESC])->limit(10)->execute();

            return compact('current', 'logs');
        }
    }

    #[GetMapping]
    public function appsAction()
    {
        $apps = $this->redisDb->hKeys(self::REDIS_KEY);
        sort($apps);

        return $apps;
    }

    #[PostMapping]
    public function createAction(string $app_id, string $env)
    {
        if ($this->redisDb->hExists(self::REDIS_KEY, $app_id)) {
            return "{$app_id}已存在";
        }

        $dotenvLog = new DotenvLog();

        $dotenvLog->app_id = $app_id;
        $dotenvLog->env = $env;

        $this->dotenvLogRepository->create($dotenvLog);

        $this->redisDb->hSet(self::REDIS_KEY, $app_id, $env);
    }

    #[PostMapping]
    public function editAction(string $app_id, string $env)
    {
        if (!$this->redisDb->hExists(self::REDIS_KEY, $app_id)) {
            return "{$app_id}不存在";
        }

        if ($this->redisDb->hGet(self::REDIS_KEY, $app_id) === $env) {
            return 0;
        }

        $dotenvLog = new DotenvLog();

        $dotenvLog->app_id = $app_id;
        $dotenvLog->env = $env;

        $this->dotenvLogRepository->create($dotenvLog);

        $this->redisDb->hSet(self::REDIS_KEY, $app_id, $env);
    }

    #[PostMapping]
    public function deleteAction(string $app_id)
    {
        $this->redisDb->hDel(self::REDIS_KEY, $app_id);
    }
}

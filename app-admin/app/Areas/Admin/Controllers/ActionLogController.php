<?php
declare(strict_types=1);

namespace App\Areas\Admin\Controllers;

use App\Controllers\Controller;
use App\Models\AdminActionLog;
use ManaPHP\Http\Controller\Attribute\AcceptVerbs;
use ManaPHP\Http\Controller\Attribute\Authorize;

class ActionLogController extends Controller
{
    #[Authorize]
    public function indexAction(int $page = 1, int $size = 10)
    {
        return AdminActionLog::select()
            ->whereCriteria($this->request->all(), ['admin_name', 'path', 'client_ip', 'created_time@=', 'tag'])
            ->orderBy(['id' => SORT_DESC])
            ->paginate($page, $size);
    }

    #[Authorize('user')]
    public function detailAction(AdminActionLog $adminActionLog)
    {
        if ($adminActionLog->admin_id === $this->identity->getId() || $this->authorization->isAllowed('detail')) {
            return $adminActionLog;
        } else {
            return '没有权限';
        }
    }

    #[AcceptVerbs(['GET'])]
    #[Authorize('user')]
    public function latestAction(int $page = 1, int $size = 10)
    {
        return AdminActionLog::select()
            ->where(['admin_id' => $this->identity->getId()])
            ->whereCriteria($this->request->all(), ['path', 'client_ip', 'created_time@=', 'tag'])
            ->orderBy(['id' => SORT_DESC])
            ->paginate($page, $size);
    }
}
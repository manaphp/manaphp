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
    public function indexAction()
    {
        return AdminActionLog::select()
            ->search(['admin_name', 'path', 'client_ip', 'created_time@=', 'tag'])
            ->orderBy(['id' => SORT_DESC])
            ->paginate();
    }

    #[Authorize('user')]
    public function detailAction(AdminActionLog $adminActionLog)
    {
        if ($adminActionLog->admin_id == $this->identity->getId() || $this->authorization->isAllowed('detail')) {
            return $adminActionLog;
        } else {
            return '没有权限';
        }
    }

    #[AcceptVerbs(['GET'])]
    #[Authorize('user')]
    public function latestAction()
    {
        return AdminActionLog::select()
            ->where(['admin_id' => $this->identity->getId()])
            ->search(['path', 'client_ip', 'created_time@=', 'tag'])
            ->orderBy(['id' => SORT_DESC])
            ->paginate();
    }
}
<?php
namespace App\Areas\User\Controllers;

use App\Models\AdminActionLog;
use ManaPHP\Mvc\Controller;

class ActionLogController extends Controller
{
    public function indexAction()
    {
        if ($this->request->isAjax()) {

            $criteria = AdminActionLog::criteria(['id', 'user_name', 'ip', 'udid', 'method', 'url', 'created_time'])
                ->whereSearch(['user_name', 'url'])
                ->orderBy(['id' => SORT_DESC]);
            return $this->response->setJsonContent($criteria->paginate());
        }
    }

    public function detailAction()
    {
        $id = $this->request->get('id');

        return $this->response->setJsonContent(AdminActionLog::get($id));
    }
}
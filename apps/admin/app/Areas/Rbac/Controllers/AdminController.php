<?php

namespace App\Areas\Rbac\Controllers;

use App\Models\Admin;
use ManaPHP\Mvc\Controller;

class AdminController extends Controller
{
    public function getAcl()
    {
        return ['list' => '@index'];
    }

    public function indexAction()
    {
        if ($this->request->isAjax()) {
            $builder = Admin::query()
                ->select(['admin_id', 'admin_name', 'status', 'login_ip', 'login_time', 'email', 'updator_name', 'creator_name', 'created_time', 'updated_time'])
                ->orderBy('admin_id DESC');

            $keyword = input('keyword', '');
            if (strpos($keyword, '@') !== false) {
                $builder->whereContains('email', $keyword);
            } else {
                $builder->whereContains(['admin_name', 'email'], $keyword);
            }

            return $builder->paginate(15);
        }
    }

    public function listAction()
    {
        return Admin::lists([], ['admin_id' => 'admin_name']);
    }

    public function lockAction()
    {
        return $this->request->isPost() ? Admin::updateOrFail(['status' => Admin::STATUS_LOCKED]) : null;
    }

    public function activeAction()
    {
        return $this->request->isPost() ? Admin::updateOrFail(['status' => Admin::STATUS_ACTIVE]) : null;
    }

    public function createAction()
    {
        if ($this->request->isPost()) {
            $admin = Admin::newOrFail();

            $admin->salt = $this->password->salt();
            $admin->password = $this->password->hash($admin->password, $admin->salt);

            return $admin->create();
        }
    }

    public function editAction()
    {
        if ($this->request->isPost()) {
            $data = input();
            $admin = Admin::get(input('admin_id'))->assign($data);
            if ($data['password'] !== '') {
                $admin->salt = $this->password->salt();
                $admin->password = $this->password->hash($data['password'], $admin->salt);
            }
            return $admin->update();
        }
    }
}
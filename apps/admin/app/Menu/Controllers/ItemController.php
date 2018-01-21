<?php
namespace App\Admin\Menu\Controllers;

use App\Admin\Menu\Models\Group;
use App\Admin\Menu\Models\Item;
use App\Admin\Rbac\Models\Permission;
use ManaPHP\Mvc\Controller;

class ItemController extends Controller
{
    public function indexAction()
    {
        if ($this->request->isAjax()) {
            $items = Item::criteria()
                ->whereRequest(['group_id'])
                ->orderBy('group_id ASC, display_order DESC, item_id ASC')
                ->execute();

            return $this->response->setJsonContent(['items' => $items]);
        }
    }

    public function createAction()
    {
        if ($this->request->isPost()) {
            try {
                $group_id = $this->request->get('group_id', '*|int');
                $item_name = $this->request->get('item_name', '*');
                $display_order = $this->request->get('display_order', '*|int');
                $permission_id = $this->request->get('permission_id', '*|int');
            } catch (\Exception $e) {
                return $this->response->setJsonContent($e);
            }

            if (!Group::exists($group_id)) {
                return $this->response->setJsonContent('group is not exists');
            }

            if (Item::exists(['group_id' => $group_id, 'item_name' => $item_name])) {
                return $this->response->setJsonContent('item name is exists');
            }

            if (!Permission::exists($permission_id)) {
                return $this->response->setJsonContent('permission is not exists');
            }

            $item = new Item();

            $item->item_name = $item_name;
            $item->group_id = $group_id;
            $item->permission_id = $permission_id;
            $item->display_order = $display_order;
            $item->creator_id = $this->userIdentity->getId();
            $item->creator_name = $this->userIdentity->getName();

            $item->create();

            return $this->response->setJsonContent(0);
        }
    }

    public function deleteAction()
    {
        if ($this->request->isPost()) {
            try {
                $item_id = $this->request->get('item_id', '*|int');
            } catch (\Exception $e) {
                return $this->response->setJsonContent($e);
            }

            $item = Item::firstOrFail($item_id);
			
            $item->delete();

            return $this->response->setJsonContent(0);
        }
    }
}
<?php
namespace Application\Admin\Controllers;

use ManaPHP\Authorization\Rbac\Annotation;
use ManaPHP\Authorization\Rbac\Models\Permission;
use ManaPHP\Mvc\Controller;

class RbacPermissionController extends Controller
{
    public function indexAction()
    {
        $this->view->setLayout();
        $items = $this->modelsManager->createBuilder()
            ->columns('*')
            ->addFrom(Permission::class)
            ->execute();

        $data = ['permissions' => $items];
        $this->view->setVar('data', $data);
    }

    public function rebuildAction()
    {
        $annotation = new Annotation();
        foreach (glob($this->alias->get('@app') . '/*') as $entry) {
            if (!is_dir($entry)) {
                continue;
            }

            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $files = glob($entry . '/Controllers/*Controller.php');
            foreach ($files as $file) {
                $file = str_replace('\\', '/', $file);

                if (preg_match('#([^/]*/[^/]*/Controllers/.*Controller)\.php$#', $file, $match) === 1) {
                    $controller = str_replace('/', '\\', $match[1]);
                    $permissions = $annotation->getPermissions($controller);
                    foreach ($permissions as $p) {
                        $permission = Permission::findFirst(['module' => $p['module'], 'controller' => $p['controller'], 'action' => $p['action']]);
                        if ($permission === false) {
                            $permission = new Permission();

                            $permission->module = $p['module'];
                            $permission->controller = $p['controller'];
                            $permission->action = $p['action'];
                            $permission->description = $p['description'];
                            $permission->created_time = time();
                            $permission->permission_type = Permission::TYPE_PENDING;

                            $permission->create();
                        }
                    }
                }
            }
        }

        return $this->response->redirect('/admin/rbac_permission');
    }
}
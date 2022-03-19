<?php

namespace App\Areas\System\Controllers;

use App\Controllers\Controller;
use ManaPHP\Http\Controller\Attribute\Authorize;
use ManaPHP\Version;

#[Authorize('@index')]
class InformationController extends Controller
{
    public function indexView()
    {
        $data = [];

        $data['framework_version'] = Version::get();
        $data['php_version'] = PHP_VERSION;
        $data['sapi'] = PHP_SAPI;

        if (function_exists('apache_get_version')) {
            $data['apache_version'] = apache_get_version();
        }

        /** @noinspection ConstantCanBeUsedInspection */
        $data['operating_system'] = php_uname();
        $data['system_time'] = date('Y-m-d H:i:s');
        $data['loaded_ini'] = '';
        $data['server_ip'] = $this->request->getServer('SERVER_ADDR');
        $data['client_ip'] = $this->request->getServer('REMOTE_ADDR');
        $data['upload_max_filesize'] = ini_get('upload_max_filesize');
        $data['post_max_size'] = ini_get('post_max_size');
        $data['loaded_ini'] = php_ini_loaded_file();
        $loaded_extensions = get_loaded_extensions();
        sort($loaded_extensions);
        $data['loaded_extensions'] = implode(', ', $loaded_extensions);
        $data['loaded_classes'] = get_declared_classes();
        $this->view->setVar('data', $data);
    }

    public function indexAction()
    {

    }
}

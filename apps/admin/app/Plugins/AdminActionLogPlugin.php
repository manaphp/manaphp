<?php
namespace App\Plugins;

use App\Models\AdminActionLog;
use ManaPHP\Plugin;

class AdminActionLogPlugin extends Plugin
{
    public function __construct()
    {
        $this->eventsManager->attachEvent('request:invoke', [$this, 'onInvoke']);
    }

    public function onInvoke()
    {
        if (in_array($this->request->getServer('REQUEST_METHOD'), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $data = array_except($this->request->get(), ['_url']);
        if (isset($data['password'])) {
            $data['password'] = '*';
        }

        $adminActionLog = new AdminActionLog();
        $adminActionLog->admin_id = $this->identity->getId(0);
        $adminActionLog->admin_name = $this->identity->getName('');
        $adminActionLog->client_ip = $this->request->getClientIp();
        $adminActionLog->method = $this->request->getMethod();
        $adminActionLog->url = parse_url($this->request->getUri(), PHP_URL_PATH);
        $adminActionLog->data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $adminActionLog->path = $this->dispatcher->getPath();
        $adminActionLog->client_udid = $this->cookies->get('CLIENT_UDID');
        $adminActionLog->create();
    }
}
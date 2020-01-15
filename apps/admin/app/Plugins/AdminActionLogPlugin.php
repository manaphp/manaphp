<?php
namespace App\Plugins;

use App\Models\AdminActionLog;
use ManaPHP\Helper\Arr;
use ManaPHP\Plugin;

class AdminActionLogPluginContext
{
    public $logged = false;
}

/**
 * Class AdminActionLogPlugin
 * @package App\Plugins
 *
 * @property-read \App\Plugins\AdminActionLogPluginContext $_context
 */
class AdminActionLogPlugin extends Plugin
{
    public function __construct()
    {
        $this->eventsManager->attachEvent('app:logAction', [$this, 'onAppLogAction']);
        $this->eventsManager->attachEvent('db:executing', [$this, 'onDbExecuting']);
    }

    public function onDbExecuting()
    {
        if (!$this->_context->logged && $this->dispatcher->isInvoking()) {
            $this->onAppLogAction();
        }
    }

    protected function _getTag()
    {
        foreach ($this->request->get() as $k => $v) {
            if (is_numeric($v)) {
                if ($k === 'id') {
                    return $v;
                } elseif (strpos($k, '_id') === strlen($k) - 3) {
                    return $v;
                }
            }
        }

        return 0;
    }

    public function onAppLogAction()
    {
        $context = $this->_context;
        if ($context->logged) {
            return;
        }
        $context->logged = true;

        $data = Arr::except($this->request->get(), ['_url']);
        if (isset($data['password'])) {
            $data['password'] = '*';
        }
        unset($data['ajax']);

        $adminActionLog = new AdminActionLog();
        $adminActionLog->admin_id = $this->identity->getId(0);
        $adminActionLog->admin_name = $this->identity->getName('');
        $adminActionLog->client_ip = $this->request->getClientIp();
        $adminActionLog->method = $this->request->getMethod();
        $adminActionLog->url = parse_url($this->request->getUri(), PHP_URL_PATH);
        $adminActionLog->tag = ((int)$this->_getTag()) & 0xFFFFFFFF;
        $adminActionLog->data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $adminActionLog->path = $this->dispatcher->getPath();
        $adminActionLog->client_udid = $this->cookies->get('CLIENT_UDID');
        $adminActionLog->create();
    }
}
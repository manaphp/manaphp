<?php

namespace App\Plugins;

use App\Models\AdminActionLog;
use ManaPHP\Helper\Arr;

class AdminActionLogPluginContext
{
    public $logged = false;
}

/**
 * @property-read \ManaPHP\Identifying\IdentityInterface   $identity
 * @property-read \ManaPHP\Http\RequestInterface           $request
 * @property-read \ManaPHP\Http\CookiesInterface           $cookies
 * @property-read \ManaPHP\Http\DispatcherInterface        $dispatcher
 * @property-read \App\Plugins\AdminActionLogPluginContext $context
 */
class AdminActionLogPlugin extends Plugin
{
    public function __construct()
    {
        $this->attachEvent('app:logAction', [$this, 'onAppLogAction']);
        $this->attachEvent('db:executing', [$this, 'onDbExecuting']);
    }

    public function onDbExecuting()
    {
        if (!$this->context->logged && $this->dispatcher->isInvoking()) {
            $this->onAppLogAction();
        }
    }

    protected function getTag()
    {
        foreach ($this->request->get() as $k => $v) {
            if (is_numeric($v)) {
                if ($k === 'id') {
                    return $v;
                } elseif (str_ends_with($k, '_id')) {
                    return $v;
                }
            }
        }

        return 0;
    }

    public function onAppLogAction()
    {
        $context = $this->context;
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
        $adminActionLog->tag = ((int)$this->getTag()) & 0xFFFFFFFF;
        $adminActionLog->data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $adminActionLog->path = $this->dispatcher->getPath();
        $adminActionLog->client_udid = $this->cookies->get('CLIENT_UDID');
        $adminActionLog->create();
    }
}
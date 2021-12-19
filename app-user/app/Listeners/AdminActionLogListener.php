<?php
declare(strict_types=1);

namespace App\Listeners;

use ManaPHP\Event\Listener;

use App\Models\AdminActionLog;
use ManaPHP\Helper\Arr;

/**
 * @property-read \ManaPHP\Identifying\IdentityInterface       $identity
 * @property-read \ManaPHP\Http\RequestInterface               $request
 * @property-read \ManaPHP\Http\CookiesInterface               $cookies
 * @property-read \ManaPHP\Http\DispatcherInterface            $dispatcher
 * @property-read \App\Listeners\AdminActionLogListenerContext $context
 */
class AdminActionLogListener extends Listener
{
    public function listen(): void
    {
        $this->attachEvent('app:adminActionLogAction', [$this, 'onAdminActionLogAction']);
        $this->attachEvent('db:executing', [$this, 'onDbExecuting']);
    }

    public function onDbExecuting(): void
    {
        if (!$this->context->logged && $this->dispatcher->isInvoking() && $this->dispatcher->getArea() === 'Admin') {
            $this->onAdminActionLogAction();
        }
    }

    protected function getTag(): int
    {
        foreach ($this->request->get() as $k => $v) {
            if (is_numeric($v)) {
                if ($k === 'id') {
                    return (int)$v;
                } elseif (str_ends_with($k, '_id')) {
                    return (int)$v;
                }
            }
        }

        return 0;
    }

    public function onAdminActionLogAction(): void
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
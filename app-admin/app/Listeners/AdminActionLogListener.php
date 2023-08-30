<?php
declare(strict_types=1);

namespace App\Listeners;

use App\Models\AdminActionLog;
use ManaPHP\Context\ContextTrait;
use ManaPHP\Db\Event\DbExecuting;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Helper\Arr;
use ManaPHP\Http\CookiesInterface;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Identifying\IdentityInterface;

class AdminActionLogListener
{
    use ContextTrait;

    #[Inject] protected IdentityInterface $identity;
    #[Inject] protected RequestInterface $request;
    #[Inject] protected CookiesInterface $cookies;
    #[Inject] protected DispatcherInterface $dispatcher;

    public function onDbExecuting(#[Event] DbExecuting $event): void
    {
        /** @var AdminActionLogListenerContext $context */
        $context = $this->getContext();

        if (!$context->logged && $this->dispatcher->isInvoking()) {
            $this->onAppLogAction(new AdminActionLog());
        }
    }

    protected function getTag()
    {
        foreach ($this->request->all() as $k => $v) {
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

    public function onAppLogAction(#[Event] DbExecuting|AdminActionLog $event): void
    {
        /** @var AdminActionLogListenerContext $context */
        $context = $this->getContext();
        if ($context->logged) {
            return;
        }

        if ($event instanceof DbExecuting) {
            if (!$this->dispatcher->isInvoking()) {
                return;
            }
        }

        $context->logged = true;

        $data = Arr::except($this->request->all(), ['_url']);
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
<?php
declare(strict_types=1);

namespace App\Listeners;

use App\Entities\AdminActionLog;
use App\Repositories\AdminActionLogRepository;
use ManaPHP\Context\ContextTrait;
use ManaPHP\Db\Event\DbExecuting;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Helper\Arr;
use ManaPHP\Http\CookiesInterface;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Identifying\IdentityInterface;

class AdminActionLogListener
{
    use ContextTrait;

    #[Autowired] protected IdentityInterface $identity;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected CookiesInterface $cookies;
    #[Autowired] protected DispatcherInterface $dispatcher;
    #[Autowired] protected AdminActionLogRepository $adminActionLogRepository;

    protected function getTag(): int
    {
        foreach ($this->request->all() as $k => $v) {
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

        $adminActionLog->admin_id = $this->identity->isGuest() ? 0 : $this->identity->getId();
        $adminActionLog->admin_name = $this->identity->isGuest() ? '' : $this->identity->getName();
        $adminActionLog->client_ip = $this->request->ip();
        $adminActionLog->method = $this->request->method();
        $adminActionLog->url = $this->request->path();
        $adminActionLog->tag = ((int)$this->getTag()) & 0xFFFFFFFF;
        $adminActionLog->data = json_stringify($data);
        $adminActionLog->handler = (string)$this->dispatcher->getHandler();
        $adminActionLog->client_udid = $this->cookies->get('CLIENT_UDID');

        $this->adminActionLogRepository->create($adminActionLog);
    }
}
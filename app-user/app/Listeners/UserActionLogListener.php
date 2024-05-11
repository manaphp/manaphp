<?php
declare(strict_types=1);

namespace App\Listeners;

use App\Models\UserActionLog;
use App\Repositories\UserActionLogRepository;
use ManaPHP\Context\ContextTrait;
use ManaPHP\Db\Event\DbExecuting;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Helper\Arr;
use ManaPHP\Http\CookiesInterface;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Identifying\IdentityInterface;
use function str_contains;

class UserActionLogListener
{
    use ContextTrait;

    #[Autowired] protected IdentityInterface $identity;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected CookiesInterface $cookies;
    #[Autowired] protected DispatcherInterface $dispatcher;
    #[Autowired] protected UserActionLogRepository $userActionLogRepository;

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

    public function onUserActionLogAction(#[Event] DbExecuting|UserActionLog $event): void
    {
        /** @var UserActionLogListenerContext $context */
        $context = $this->getContext();
        if ($context->logged) {
            return;
        }

        if ($event instanceof DbExecuting) {
            if (!$this->dispatcher->isInvoking()
                || !str_contains(
                    $this->dispatcher->getController(), '\\Areas\\User\\'
                )
            ) {
                return;
            }
        }

        $context->logged = true;

        $data = Arr::except($this->request->all(), ['_url']);
        if (isset($data['password'])) {
            $data['password'] = '*';
        }
        unset($data['ajax']);

        $userActionLog = new UserActionLog();

        $userActionLog->user_id = $this->identity->isGuest() ? 0 : $this->identity->getId();
        $userActionLog->user_name = $this->identity->isGuest() ? '' : $this->identity->getName();
        $userActionLog->client_ip = $this->request->ip();
        $userActionLog->method = $this->request->method();
        $userActionLog->url = $this->request->path();
        $userActionLog->tag = ((int)$this->getTag()) & 0xFFFFFFFF;
        $userActionLog->data = json_stringify($data);
        $userActionLog->handler = (string)$this->dispatcher->getHandler();
        $userActionLog->client_udid = $this->cookies->get('CLIENT_UDID');

        $this->userActionLogRepository->create($userActionLog);
    }
}
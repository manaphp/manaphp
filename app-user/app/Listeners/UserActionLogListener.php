<?php
declare(strict_types=1);

namespace App\Listeners;

use App\Models\UserActionLog;
use ManaPHP\Context\ContextTrait;
use ManaPHP\Db\Event\DbExecuting;
use ManaPHP\Eventing\Attribute\Event;
use ManaPHP\Helper\Arr;
use ManaPHP\Http\CookiesInterface;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Identifying\IdentityInterface;

class UserActionLogListener
{
    use ContextTrait;

    protected IdentityInterface $identity;
    protected RequestInterface $request;
    protected CookiesInterface $cookies;
    protected DispatcherInterface $dispatcher;

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
            if (!$this->dispatcher->isInvoking() || $this->dispatcher->getArea() !== 'User') {
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
        $userActionLog->client_ip = $this->request->getClientIp();
        $userActionLog->method = $this->request->getMethod();
        $userActionLog->url = parse_url($this->request->getUri(), PHP_URL_PATH);
        $userActionLog->tag = ((int)$this->getTag()) & 0xFFFFFFFF;
        $userActionLog->data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $userActionLog->path = $this->dispatcher->getPath();
        $userActionLog->client_udid = $this->cookies->get('CLIENT_UDID');
        $userActionLog->create();
    }
}
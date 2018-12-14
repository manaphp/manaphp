<?php
namespace App\Listeners;

use App\Areas\User\Controllers\SessionController;
use App\Models\AdminActionLog;
use ManaPHP\Event\Listener;

/**
 * Class ActionInvokerListener
 * @package App\Listeners
 */
class DispatcherListener extends Listener
{
    /**
     * @param \ManaPHP\DispatcherInterface $dispatcher
     * @param string                       $action
     *
     * @return mixed|void
     */
    public function onBeforeInvoke($dispatcher, $action)
    {
        if ($this->request->isGet()) {
            return;
        }
        if ($dispatcher->getController() instanceof SessionController) {
            return;
        }

        $data = $_REQUEST;
        unset($data['_url']);

        $adminActionLog = new AdminActionLog();
        $adminActionLog->user_id = $this->identity->getId(0);
        $adminActionLog->user_name = $this->identity->getName('');
        $adminActionLog->ip = substr($this->request->getClientIp(), 0, 16);
        $adminActionLog->method = $this->request->getMethod();
        $adminActionLog->url = parse_url($this->request->getUri(), PHP_URL_PATH);
        $adminActionLog->data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $adminActionLog->path = $this->authorization->generatePath(get_class($dispatcher->getControllerInstance()), $action);
        $adminActionLog->udid = $this->cookies->get('CLIENT_UDID');
        $adminActionLog->created_time = time();
        $adminActionLog->create();
    }
}
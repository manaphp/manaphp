<?php
namespace ManaPHP\Security;

use ManaPHP\Component;
use ManaPHP\Security\RateLimiter\Exception as RateLimiterException;
use ManaPHP\Utility\Text;

/**
 * Class RateLimiter
 *
 * @package ManaPHP\Security
 * @property \ManaPHP\Mvc\DispatcherInterface              $dispatcher
 * @property \ManaPHP\Authentication\UserIdentityInterface $userIdentity
 * @property \ManaPHP\Http\RequestInterface                $request
 */
abstract class RateLimiter extends Component implements RateLimiterInterface
{
    /**
     * @param string|array $controllerAction
     * @param int          $duration
     * @param int          $ip_times
     * @param int          $user_times
     *
     * @return void
     * @throws \ManaPHP\Security\RateLimiter\Exception
     */
    public function limit($controllerAction, $duration, $ip_times, $user_times = null)
    {
        if ($controllerAction === null) {
            $resource = $this->dispatcher->getControllerName() . ':' . $this->dispatcher->getActionName();
            $this->limitAny($resource, $duration, $ip_times, $user_times);
        } else {
            if (is_array($controllerAction)) {
                $resource = basename($controllerAction[0], 'Controller') . ':' . lcfirst(Text::camelize($controllerAction[1]));
            } else {
                $parts = explode(':', $controllerAction);
                if (count($parts) !== 2) {
                    throw new RateLimiterException('`:controllerAction` controllerAction is invalid: the correct format is `controller:action`',
                        ['controllerAction' => $controllerAction]);
                }
                $resource = Text::camelize($parts[0]) . ':' . lcfirst(Text::camelize($parts[1]));
            }

            if ($resource === $this->dispatcher->getControllerName() . ':' . $this->dispatcher->getActionName()) {
                $this->limitAny($controllerAction, $duration, $ip_times, $user_times);
            }
        }
    }

    /**
     * @param string $id
     * @param string $resource
     * @param int    $duration
     * @param int    $times
     *
     * @return mixed
     */
    abstract protected function _limit($id, $resource, $duration, $times);

    /**
     * @param string $resource
     * @param int    $duration
     * @param int    $ip_times
     * @param int    $user_times
     *
     * @return void
     * @throws \ManaPHP\Security\RateLimiter\Exception
     */
    public function limitAny($resource, $duration, $ip_times, $user_times = null)
    {
        $userId = $this->userIdentity->getId();
        if ($userId) {
            $id = $userId;
            $times = $user_times ?: $ip_times;
        } else {
            $id = $this->request->getClientAddress();
            $times = $ip_times;
        }

        if (!$this->_limit($id, $this->dispatcher->getModuleName() . ':' . $resource, $duration, $times)) {
            throw new RateLimiterException('rate limit is exceed.');
        }
    }
}
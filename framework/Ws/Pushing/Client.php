<?php
declare(strict_types=1);

namespace ManaPHP\Ws\Pushing;

use ManaPHP\Component;
use ManaPHP\Event\EventTrait;
use ManaPHP\Exception\MissingFieldException;

/**
 * @property-read \ManaPHP\Messaging\PubSubInterface $pubSub
 */
class Client extends Component implements ClientInterface
{
    use EventTrait;

    protected string $endpoint;
    protected string $prefix;

    public function __construct(string $endpoint, string $prefix = 'ws_pushing:')
    {
        $this->endpoint = $endpoint;
        $this->prefix = $prefix;
    }

    protected function push(string $type, int|string|array $receivers, string|array $message, ?string $endpoint): void
    {
        if (is_array($receivers)) {
            $receivers = implode(',', $receivers);
        }

        if (!is_string($message)) {
            $message = json_stringify($message);
        }

        if (($endpoint = $endpoint ?? $this->endpoint) === null) {
            throw new MissingFieldException($endpoint);
        }

        $this->fireEvent('wspClient:push', compact('type', 'receivers', 'message', 'endpoint'));

        $this->pubSub->publish($this->prefix . "$endpoint:$type:$receivers", $message);
    }

    public function pushToId(int|array $receivers, string|array $message, ?string $endpoint = null): void
    {
        $this->push('id', $receivers, $message, $endpoint);
    }

    public function pushToName(string|array $receivers, string|array $message, ?string $endpoint = null): void
    {
        $this->push('name', $receivers, $message, $endpoint);
    }

    public function pushToRoom(string|array $receivers, string|array $message, ?string $endpoint = null): void
    {
        $this->push('room', $receivers, $message, $endpoint);
    }

    public function pushToRole(string|array $receivers, string|array $message, ?string $endpoint = null): void
    {
        $this->push('role', $receivers, $message, $endpoint);
    }

    public function pushToAll(string|array $message, ?string $endpoint = null): void
    {
        $this->push('all', '*', $message, $endpoint);
    }

    public function broadcast(string|array $message, ?string $endpoint = null): void
    {
        $this->push('broadcast', '*', $message, $endpoint);
    }
}

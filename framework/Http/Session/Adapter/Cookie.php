<?php
declare(strict_types=1);

namespace ManaPHP\Http\Session\Adapter;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Http\AbstractSession;
use ManaPHP\Http\Session\Adapter\Cookie\Exception as CookieException;
use ManaPHP\Security\CryptInterface;

class Cookie extends AbstractSession
{
    #[Inject]
    protected CryptInterface $crypt;

    protected string $key;

    public function __construct(?string $key = null, int $ttl = 3600, int $lazy = 60, string $name = "PHPSESSID",
        string $serializer = 'json', array $params = []
    ) {
        parent::__construct($ttl, $lazy, $name, $serializer, $params);

        $this->key = $key ?? $this->crypt->getDerivedKey('cookieSession');
    }

    public function do_read(string $session_id): string
    {
        $data = $this->cookies->get($session_id) ?: '';
        if ($data === '') {
            return '';
        }

        $parts = explode('.', $data, 2);

        if (count($parts) !== 2) {
            throw new CookieException(['format invalid: `:cookie`', 'cookie' => $data]);
        }

        if (md5($parts[0] . $this->key) !== $parts[1]) {
            throw new CookieException(['hash invalid: `:cookie`', 'cookie' => $data]);
        }

        $payload = json_parse($parts[0]);
        if (!is_array($payload)) {
            throw new CookieException(['payload invalid: `:cookie`', 'cookie' => $data]);
        }

        if (time() > $payload['exp']) {
            return '';
        }

        return $payload['data'];
    }

    public function do_write(string $session_id, string $data, int $ttl): bool
    {
        $params = session_get_cookie_params();

        $payload = base64_encode(json_stringify(['exp' => time() + $ttl, 'data' => $data]));
        $this->cookies->set(
            $session_id,
            $payload . '.' . md5($payload . $this->key),
            $params['lifetime'],
            $params['path'],
            $params['domain'],
            $params['secure']
        );

        return true;
    }

    public function do_touch(string $session_id, int $ttl): bool
    {
        return false;
    }

    public function do_destroy(string $session_id): void
    {
        $this->cookies->delete($session_id);
    }

    public function do_gc(int $ttl): void
    {
    }
}
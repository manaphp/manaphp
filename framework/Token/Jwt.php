<?php
declare(strict_types=1);

namespace ManaPHP\Token;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Security\CryptInterface;
use function count;
use function is_array;

class Jwt implements JwtInterface
{
    #[Autowired] protected CryptInterface $crypt;

    #[Autowired] protected string $alg = 'HS256';
    #[Autowired] protected ?string $key;

    protected function base64UrlEncode(string $str): string
    {
        return strtr(rtrim(base64_encode($str), '='), '+/', '-_');
    }

    protected function base64UrlDecode(string $str): ?string
    {
        $v = base64_decode(strtr($str, '-_', '+/'));
        return $v === false ? null : $v;
    }

    public function encode(array $claims, int $ttl, ?string $key = null): string
    {
        $key = $key ?? $this->key ?? $this->crypt->getDerivedKey('jwt');

        $claims['iat'] = time();
        $claims['exp'] = time() + $ttl;

        $header = $this->base64UrlEncode(json_stringify(['alg' => $this->alg, 'typ' => 'JWT']));
        $payload = $this->base64UrlEncode(json_stringify($claims));
        $hmac = hash_hmac(strtr($this->alg, ['HS' => 'sha']), "$header.$payload", $key, true);
        $signature = $this->base64UrlEncode($hmac);

        return "$header.$payload.$signature";
    }

    public function decode(string $token, bool $verify = true, ?string $key = null): array
    {
        if ($token === '') {
            throw new NoCredentialException('No Credentials');
        }

        $parts = explode('.', $token, 5);
        if (count($parts) !== 3) {
            throw new MalformedException('The JWT must have three dots');
        }

        list($header, $payload) = $parts;

        //DO NOT use json_parse, it maybe generates a lot of Exceptions
        /** @noinspection JsonEncodingApiUsageInspection */
        if (!is_array($claims = json_decode($this->base64UrlDecode($payload), true))) {
            throw new MalformedException('payload is not array.');
        }

        //DO NOT use json_parse, it maybe generates a lot of Exceptions
        /** @noinspection JsonEncodingApiUsageInspection */
        $decoded_header = json_decode($this->base64UrlDecode($header), true);
        if (!$decoded_header) {
            throw new MalformedException('The JWT header is not distinguished');
        }

        if (!isset($decoded_header['alg'])) {
            throw new MalformedException('The JWT alg field is missing');
        }

        if ($decoded_header['alg'] !== $this->alg) {
            $decoded_alg = $decoded_header['alg'];
            throw new MalformedException(['The JWT alg `{1}` is not same as {2}', $decoded_alg, $this->alg]);
        }

        if (!$decoded_header['typ']) {
            throw new MalformedException(['The JWT typ field is missing: `{token}`', 'token' => $token]);
        }

        if ($decoded_header['typ'] !== 'JWT') {
            throw new MalformedException(['The JWT typ `{typ}` is not JWT', 'typ' => $decoded_header['typ']]);
        }

        if (isset($claims['exp']) && time() > $claims['exp']) {
            throw new ExpiredException('token is expired.');
        }

        if (isset($claims['nbf']) && time() < $claims['nbf']) {
            throw new NotBeforeException('token is not active.');
        }

        if ($verify) {
            $this->verify($token, $key);
        }

        return $claims;
    }

    public function verify(string $token, ?string $key = null): void
    {
        if (($pos = strrpos($token, '.')) === false) {
            throw new MalformedException('The JWT must have three dots');
        }

        $key = $key ?? $this->key ?? $this->crypt->getDerivedKey('jwt');

        $data = substr($token, 0, $pos);
        $signature = substr($token, $pos + 1);
        $hmac = hash_hmac(strtr($this->alg, ['HS' => 'sha']), $data, $key, true);

        if ($this->base64UrlEncode($hmac) !== $signature) {
            throw new SignatureException('signature is not corrected');
        }
    }
}
<?php
declare(strict_types=1);

namespace ManaPHP\Token;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Security\CryptInterface $crypt
 */
class Jwt extends Component implements JwtInterface
{
    protected string $alg = 'HS256';
    protected string $key;

    public function __construct(array $options = [])
    {
        if (isset($options['alg'])) {
            $this->alg = $options['alg'];
        }

        if (isset($options['key'])) {
            $this->key = $options['key'];
        } else {
            $this->key = $this->crypt->getDerivedKey('jwt');
        }
    }

    public function base64UrlEncode(string $str): string
    {
        return strtr(rtrim(base64_encode($str), '='), '+/', '-_');
    }

    public function base64UrlDecode(string $str): false|string
    {
        return base64_decode(strtr($str, '-_', '+/'));
    }

    public function encode(array $claims, int $ttl, ?string $key = null): string
    {
        $claims['iat'] = time();
        $claims['exp'] = time() + $ttl;

        $header = $this->base64UrlEncode(json_stringify(['alg' => $this->alg, 'typ' => 'JWT']));
        $payload = $this->base64UrlEncode(json_stringify($claims));
        $hmac = hash_hmac(strtr($this->alg, ['HS' => 'sha']), "$header.$payload", $key ?? $this->key, true);
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
            throw new MalformedException(['The JWT alg `%s` is not same as %s', $decoded_alg, $this->alg]);
        }

        if (!$decoded_header['typ']) {
            throw new MalformedException(['The JWT typ field is missing: `:token`', 'token' => $token]);
        }

        if ($decoded_header['typ'] !== 'JWT') {
            throw new MalformedException(['The JWT typ `:typ` is not JWT', 'typ' => $decoded_header['typ']]);
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

        $data = substr($token, 0, $pos);
        $signature = substr($token, $pos + 1);
        $hmac = hash_hmac(strtr($this->alg, ['HS' => 'sha']), $data, $key, true);

        if ($this->base64UrlEncode($hmac) !== $signature) {
            throw new SignatureException('signature is not corrected');
        }
    }

    public function dump(): array
    {
        $data = parent::dump();
        $data['key'] = '***';

        return $data;
    }
}
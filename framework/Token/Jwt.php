<?php
declare(strict_types=1);

namespace ManaPHP\Token;

use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Encoding\Base64UrlInterface;
use ManaPHP\Security\CryptInterface;

class Jwt extends Component implements JwtInterface
{
    #[Inject] protected CryptInterface $crypt;
    #[Inject] protected Base64UrlInterface $base64Url;

    #[Value] protected string $alg = 'HS256';
    #[Value] protected ?string $key;

    public function encode(array $claims, int $ttl, ?string $key = null): string
    {
        $key = $key ?? $this->key ?? $this->crypt->getDerivedKey('jwt');

        $claims['iat'] = time();
        $claims['exp'] = time() + $ttl;

        $header = $this->base64Url->encode(json_stringify(['alg' => $this->alg, 'typ' => 'JWT']));
        $payload = $this->base64Url->encode(json_stringify($claims));
        $hmac = hash_hmac(strtr($this->alg, ['HS' => 'sha']), "$header.$payload", $key, true);
        $signature = $this->base64Url->encode($hmac);

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
        if (!is_array($claims = json_decode($this->base64Url->decode($payload), true))) {
            throw new MalformedException('payload is not array.');
        }

        //DO NOT use json_parse, it maybe generates a lot of Exceptions
        /** @noinspection JsonEncodingApiUsageInspection */
        $decoded_header = json_decode($this->base64Url->decode($header), true);
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

        $key = $key ?? $this->key ?? $this->crypt->getDerivedKey('jwt');

        $data = substr($token, 0, $pos);
        $signature = substr($token, $pos + 1);
        $hmac = hash_hmac(strtr($this->alg, ['HS' => 'sha']), $data, $key, true);

        if ($this->base64Url->encode($hmac) !== $signature) {
            throw new SignatureException('signature is not corrected');
        }
    }
}
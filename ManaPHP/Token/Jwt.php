<?php

namespace ManaPHP\Token;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Security\CryptInterface $crypt
 */
class Jwt extends Component implements JwtInterface
{
    /**
     * @var string
     */
    protected $alg = 'HS256';

    /**
     * @var string|array
     */
    protected $secret;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['alg'])) {
            $this->alg = $options['alg'];
        }

        if (isset($options['crypt'])) {
            $this->injections['crypt'] = $options['crypt'];
        }

        if (isset($options['secret'])) {
            $this->secret = $options['secret'];
        } else {
            $this->secret = $this->crypt->getDerivedKey('jwt:' . APP_ID);
        }
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public function base64UrlEncode($str)
    {
        return strtr(rtrim(base64_encode($str), '='), '+/', '-_');
    }

    /**
     * @param string $str
     *
     * @return false|string
     */
    public function base64UrlDecode($str)
    {
        return base64_decode(strtr($str, '-_', '+/'));
    }

    /**
     * @param array  $claims
     * @param int    $ttl
     * @param string $secret
     *
     * @return string
     */
    public function encode($claims, $ttl, $secret = null)
    {
        $claims['iat'] = time();
        $claims['exp'] = time() + $ttl;

        $header = $this->self->base64UrlEncode(json_stringify(['alg' => $this->alg, 'typ' => 'JWT']));
        $payload = $this->self->base64UrlEncode(json_stringify($claims));
        $hmac = hash_hmac(strtr($this->alg, ['HS' => 'sha']), "$header.$payload", $secret ?? $this->secret, true);
        $signature = $this->self->base64UrlEncode($hmac);

        return "$header.$payload.$signature";
    }

    /**
     * @param string       $token
     * @param bool         $verify
     * @param string|array $secrets
     *
     * @return array
     */
    public function decode($token, $verify = true, $secrets = null)
    {
        if ($token === null || $token === '') {
            throw new NoCredentialException('No Credentials');
        }

        $parts = explode('.', $token, 5);
        if (count($parts) !== 3) {
            throw new MalformedException('The JWT must have three dots');
        }

        list($header, $payload) = $parts;

        //DO NOT use json_parse, it maybe generates a lot of Exceptions
        if (!is_array($claims = json_decode($this->self->base64UrlDecode($payload), true))) {
            throw new MalformedException('payload is not array.');
        }

        //DO NOT use json_parse, it maybe generates a lot of Exceptions
        $decoded_header = json_decode($this->self->base64UrlDecode($header), true);
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
            $this->self->verify($token, $secrets);
        }

        return $claims;
    }

    /**
     * @param string       $token
     * @param string|array $secrets
     */
    public function verify($token, $secrets = null)
    {
        if (($pos = strrpos($token, '.')) === false) {
            throw new MalformedException('The JWT must have three dots');
        }

        $data = substr($token, 0, $pos);
        $signature = substr($token, $pos + 1);

        $success = false;
        foreach ((array)($secrets ?? $this->secret) as $secret) {
            $hmac = hash_hmac(strtr($this->alg, ['HS' => 'sha']), $data, $secret, true);
            if ($this->self->base64UrlEncode($hmac) === $signature) {
                $success = true;
                break;
            }
        }

        if (!$success) {
            throw new SignatureException('signature is not corrected');
        }
    }

    /**
     * @return array
     */
    public function dump()
    {
        $data = parent::dump();
        $data['secret'] = '***';

        return $data;
    }
}
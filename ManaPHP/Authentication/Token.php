<?php
namespace ManaPHP\Authentication;

use ManaPHP\Component;
use ManaPHP\Exception\PreconditionException;

abstract class Token extends Component implements TokenInterface
{
    /**
     * @var string
     */
    protected $_alg;
    /**
     * @var array
     */
    protected $_key = [];

    /**
     * @var int
     */
    protected $_ttl = 86400;

    /**
     * @var array
     */
    protected $_claims;

    /**
     * @param string $claim
     *
     * @return int|string|array
     */
    public function getClaim($claim)
    {
        if (!$this->_claims) {
            throw new PreconditionException('token is not parsed');
        }

        if ($claim) {
            return isset($this->_claims[$claim]) ? $this->_claims[$claim] : null;
        } else {
            return $this->_claims;
        }
    }

    /**
     * @param string $claim
     *
     * @return bool
     */
    public function hasClaim($claim)
    {
        if (!$this->_claims) {
            throw new PreconditionException('token is not parsed');
        }

        return isset($this->_claims[$claim]);
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public function base64urlEncode($str)
    {
        return strtr(rtrim(base64_encode($str), '='), '+/', '-_');
    }

    /**
     * @param string $str
     *
     * @return bool|string
     */
    public function base64urlDecode($str)
    {
        return base64_decode(strtr($str, '-_', '+/'));
    }
}
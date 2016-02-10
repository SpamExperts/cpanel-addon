<?php

class SpamFilter_Validate_Hostname extends Zend_Validate_Abstract {

    const ASCII_HOSTNAME_REGEX = '~^[A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+$~i';

    const INVALID_HOSTNAME = 'invalidHostname';
    const CANNOT_DECODE_PUNYCODE  = 'cannotDecodePunyCode';
    const errOk  = 'errOk';


    protected $_messageTemplates = array(
        self::INVALID_HOSTNAME        => "'%value%' is not a valid hostname.",
        self::CANNOT_DECODE_PUNYCODE  => "'%value%' appears to be a DNS hostname but the given punycode notation cannot be decoded",
        self::errOk        => "'%value%' is a valid hostname.",
    );

    /**
     * isValid
     * Validate Input
     *
     * @param $value
     * @return bool True/False
     *
     * @access public
     * @see _error()
     */
    public function isValid($value)
    {
        if (!is_scalar($value) || !preg_match('~[a-z]+~i', $value)) {
            $this->_error(self::INVALID_HOSTNAME, $value);
            return false;
        }

        if ('localhost' == strtolower($value)) {
            return true;
        }

        $idn = new IDNA_Convert;
        $ascii = $idn->encode($value);

        $matched = preg_match(self::ASCII_HOSTNAME_REGEX, $ascii, $matches);

        if ($matched) {
            // We should check if the hostnames actually exists
            // in the matches(idn fixes some hostnames like .host.com => host.com)
            foreach ($matches as $match) {
                if ($idn->decode($match) == $value) {
                    return true;
                }
            }
        }

        $this->_error(self::INVALID_HOSTNAME, $value);
        return false;

    }

}
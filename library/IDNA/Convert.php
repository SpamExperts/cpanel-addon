<?php

/**
 * Encode/decode Internationalized Domain Names.
 *
 * The class allows to convert internationalized domain names
 * (see RFC 3490 for details) as they can be used with various registries worldwide
 * to be translated between their original (localized) form and their encoded form
 * as it will be used in the DNS (Domain Name System).
 *
 * The class provides two public methods, encode() and decode(), which do exactly
 * what you would expect them to do. You are allowed to use complete domain names,
 * simple strings and complete email addresses as well. That means, that you might
 * use any of the following notations:
 *
 * - www.n�rgler.com
 * - xn--nrgler-wxa
 * - xn--brse-5qa.xn--knrz-1ra.info
 *
 */
class IDNA_Convert
{

    /**
     * Convert domain name from IDNA ASCII to Unicode
     * @param string $encoded Domain name (Punycode)
     * @return   string   Decoded Domain name (UTF-8)
     */
    public function decode($encoded)
    {
        return idn_to_utf8($encoded);
    }

    /**
     * Convert domain name to IDNA ASCII form
     * @param string $decoded Domain name (UTF-8)
     * @return   string   Encoded Domain name (Punycode)
     */
    public function encode($decoded)
    {
        return idn_to_ascii($decoded);
    }

}

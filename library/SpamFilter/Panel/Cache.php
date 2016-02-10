<?php
/**
*************************************************************************
*                                                                       *
* ProSpamFilter                                                         *
* Bridge between Webhosting panels & SpamExperts filtering		        *
*                                                                       *
* Copyright (c) 2010-2011 SpamExperts B.V. All Rights Reserved,         *
*                                                                       *
*************************************************************************
*                                                                       *
* Email: support@spamexperts.com                                        *
* Website: htttp://www.spamexperts.com                                  *
*                                                                       *
*************************************************************************
*                                                                       *
* This software is furnished under a license and may be used and copied *
* only in accordance with the  terms of such license and with the       *
* inclusion of the above copyright notice. No title to and ownership    *
* of the software is  hereby  transferred.                              *
*                                                                       *
* You may not reverse engineer, decompile or disassemble this software  *
* product or software product license.                                  *
*                                                                       *
* SpamExperts may terminate this license if you don't comply with any   *
* of the terms and conditions set forth in our end user                 *
* license agreement (EULA). In such event, licensee agrees to return    *
* licensor  or destroy  all copies of software upon termination of the  *
* license.                                                              *
*                                                                       *
* Please see the EULA file for the full End User License Agreement.     *
*                                                                       *
*************************************************************************
* @category  SpamExperts
* @package   ProSpamFilter
* @author    $Author$
* @copyright Copyright (c) 2011, SpamExperts B.V., All rights Reserved. (http://www.spamexperts.com)
* @license   Closed Source
* @version   3.0
* @link      https://my.spamexperts.com/kb/34/Addons
* @since     2.5
*/

/** @noinspection PhpUndefinedClassInspection */
class SpamFilter_Panel_Cache
{
    /**
     * Method for getting cached contents
     *
     * @access public
     * @static
     * @param string $key
     * @return mixed
     */
    public static function get($key)
    {
        return self::getHandler()->load(self::key($key));
    }

    /**
     * Method for cached contents setting
     *
     * @access public
     * @static
     * @param string $key
     * @param mixed $data
     * @param integer $lifetime
     * @return void
     */
    public static function set($key, $data, $lifetime = 600)
    {
        self::getHandler($lifetime)->save($data, self::key($key));
    }

    /**
     * Method for cached contents testing
     *
     * @access public
     * @static
     * @param string $key
     * @return boolean true if no problem
     */
    public static function test($key)
    {
        return self::getHandler()->test(self::key($key));
    }

    /**
     * Method for cached contents clearing
     *
     * @access public
     * @static
     *
     * @param string $key
     * @param bool   $transformKey
     *
     * @return void
     */
    public static function clear($key, $transformKey = true)
    {
        self::getHandler()->remove(((true === $transformKey) ? self::key($key) : $key));
    }

    /**
     * Private method for cache handler getting
     *
     * @access private
     * @param integer $lifetime
     * @return Zend_Cache_Core
     */
    private static function getHandler($lifetime = 600)
    {
        return Zend_Cache::factory('Core', 'File',
            array(
                'lifetime' => $lifetime,
                'automatic_serialization' => true,
            ),
            array(
                'cache_dir' => (SpamFilter_Core::isWindows())? TMP_PATH . DS . 'cache' . DS : realpath(dirname(__FILE__) . '/../../..') . DS . 'tmp' . DS . 'cache' . DS
            )
        );
    }

    /**
     * Private method for cache entry key composing
     *
     * @access private
     * @param string $key
     * @return string
     */
    private static function key($key)
    {
        /** @noinspection PhpUndefinedClassInspection */
        return __CLASS__ . $key . '_'. md5(SpamFilter_Core::getUsername());
    }

    /**
     * Method for getting a filtered list of existing cache ids
     *
     * @static
     * @param string $filter
     * @return array
     */
    public static function listMatches($filter = '.*')
    {
        $result = array();

        foreach (self::getHandler()->getIds() as $id) {
            if (preg_match('~' . $filter . '~i', $id)) {
                $result[] = $id;
            }
        }

        return $result;
    }
}

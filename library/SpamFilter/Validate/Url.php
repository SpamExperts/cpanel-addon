<?php
/*
*************************************************************************
*                                                                       *
* ProSpamFilter                                                         *
* Bridge between Webhosting panels & SpamExperts filtering				*
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
* @since     2.0
*/
class SpamFilter_Validate_Url extends Zend_Validate_Abstract
{
	const INVALID_URL = 'invalidUrl';
	const NO_SPAMPANEL = 'noSpampanel';
	const NO_SSL = 'noSsl';

	protected $_messageTemplates = array(
		self::INVALID_URL => "'%value%' is not a valid URL.",
		self::NO_SPAMPANEL => "'%value%' is not an AntiSpam Web Interface URL.",
		self::NO_SSL => "'%value%' is a SSL secured page which we cannot load using the current configuration.",
	);

/**
 * isValid
 * Validate Input
 *
 * @param $value Input to validate
 *
 * @return bool True/False
 *
 * @access public
 * @see _error()
 */
	public function isValid($value)
	{
		$valueString = (string) $value;
		$this->_setValue($valueString);

		// Check if the URL is syntax correct.
		if (!Zend_Uri::check($value))
		{
			$this->_error( self::INVALID_URL );
			return false;
		}

		// Check if the URL is a SpamPanel url
		$url = "{$value}/version.txt";
		$version = SpamFilter_HTTP::getContent( $url );

		if( empty($version) || $version == false )
		{
			$this->_error( self::NO_SPAMPANEL );
			return false;
		}

        //Check if URL contain more than 2 forward slashes
        if (!preg_match('@^https?://[^/]+$@i',$value)){
            $this->_error( self::NO_SPAMPANEL );
            return false;
        }

		return true;
	}
}

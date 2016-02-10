<?php

/*
 * ************************************************************************
 *                                                                       *
 * ProSpamFilter                                                         *
 * Bridge between Webhosting panels & SpamExperts filtering			    *
 *                                                                       *
 * Copyright (c) 2010-2011 SpamExperts B.V. All Rights Reserved,         *
 *                                                                       *
 * ************************************************************************
 *                                                                       *
 * Email: support@spamexperts.com                                        *
 * Website: htttp://www.spamexperts.com                                  *
 *                                                                       *
 * ************************************************************************
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
 * ************************************************************************
 * @category  SpamExperts
 * @package   ProSpamFilter
 * @author    $Author$
 * @copyright Copyright (c) 2011, SpamExperts B.V., All rights Reserved. (http://www.spamexperts.com)
 * @license   Closed Source
 * @version   3.0
 * @link      https://my.spamexperts.com/kb/34/Addons
 * @since     2.0
 */

class SpamFilter_Validate_SPFRecord extends Zend_Validate_Abstract {

    const NOT_SPF = 'invalidRecord';

    protected $_messageTemplates = array(
        self::NOT_SPF => "SPF record should start with 'v=spf'.",
    );

    /**
     * isValid
     * Validate constructed input
     *
     * @param $value Value to validate
     *
     * @return bool Status
     *
     * @access public
     * @see _error()
     */
    public function isValid($value) {
        if (substr($value, 0, 5) != 'v=spf') {
            $this->_error(self::NOT_SPF);
            return false;
        }
        return true;
    }

}

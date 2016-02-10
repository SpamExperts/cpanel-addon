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
* @since     2.5
*/

class ErrorController extends Zend_Controller_Action
{
    /** @var Zend_Translate_Adapter_Gettext */
    var $t;

    /**
     * This action handles
     *    - Application errors
     *    - Errors in the controller chain arising from missing
     *     controller classes and/or action methods
     */
    public function init()
    {
        Zend_Layout::startMvc(
            array(
                 'layoutPath' => BASE_PATH . DS . 'application' . DS . 'views' . DS . 'layouts',
                 'layout'     => 'default'
            )
        );

        // Get the translator
        $this->t = Zend_Registry::get('translator');

        $this->view->headTitle("An error has occured");
		$this->view->headStyle()->appendStyle( file_get_contents(BASE_PATH . DS . 'public' . DS . 'css' . DS . 'bootstrap.min.css') );
		$this->view->headStyle()->appendStyle( file_get_contents(BASE_PATH . DS . 'public' . DS . 'css' . DS . 'bootstrap-responsive.min.css') );
		$this->view->headStyle()->appendStyle( file_get_contents(BASE_PATH . DS . 'public' . DS . 'css' . DS . 'addon.css') );
		$this->view->headScript()->appendScript( file_get_contents(BASE_PATH . DS . 'public' . DS . 'js' . DS . 'jquery.min.js') );
		$this->view->headScript()->appendScript( file_get_contents(BASE_PATH . DS . 'public' . DS . 'js' . DS . 'bootstrap.min.js') );

    }

    public function errorAction()
    {
        $content = null;
        $errors  = $this->_getParam('error_handler');
        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER :
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION :
                $content .= "<h1>404 Page not found!</h1>" . PHP_EOL;
                $content .= "<p>The page you requested was not found.</p>";
                $exception = $errors->exception;
                Zend_Registry::get('logger')->debug("[Frontend] Error:" . $exception->getMessage());

                break;

            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONFIG :
                $content   = "<div class='alert alert-block alert-error'>
                                  <button type='button' class='close' data-dismiss='alert'>&times;</button>
                                  <h4>Error!</h4>
                                  <pre>Unable to retrieve configuration, please contact your provider for more information.</pre>
                              </div>";
                $exception = $errors->exception;
                Zend_Registry::get('logger')->debug(
                    "[Frontend] " . $exception->getMessage() . PHP_EOL . $exception->getTraceAsString()
                );

                break;

            default :
                $exception = $errors->exception;
                if (strpos($exception->getMessage(),'general_config')=== false) {
                    $content   = "<div class='alert alert-block alert-error'>
                                  <button type='button' class='close' data-dismiss='alert'>&times;</button>
                                  <h4>Error!</h4>
                                  <pre>" . $exception->getMessage() . "</pre>
                                  <pre>" . $exception->getTraceAsString() . "</pre>
                              </div>";
                } else {
                    $content   = "<div class='alert alert-block alert-error'>
                                  <button type='button' class='close' data-dismiss='alert'>&times;</button>
                                  <h4>Error!</h4>
                                  <pre>Unable to retrieve configuration, please contact your provider for more information.</pre>
                              </div>";
                }

                Zend_Registry::get('logger')->debug(
                    "[Frontend] " . $exception->getMessage() . PHP_EOL . $exception->getTraceAsString()
                );

                break;
        }
        $this->view->layout()->setLayout("default");
        // Clear previous content
        $this->getResponse()->clearBody();
        $this->view->content = $content;
    }
}

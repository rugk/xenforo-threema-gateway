<?php
/**
 * Callback for Threema Gateway.
 *
 * Inspired by PayPal Callback included in XenForo.
 * You can remove this file if you uninstalled the Threema Gateway add-on.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

$startTime = microtime(true);
$fileDir   = dirname(__FILE__);
if (!file_exists($fileDir . '/library/XenForo/Autoloader.php')) {
    // second try
    $fileDir = '.';
}

require($fileDir . '/library/XenForo/Autoloader.php');
XenForo_Autoloader::getInstance()->setupAutoloader($fileDir . '/library');

XenForo_Application::initialize($fileDir . '/library', $fileDir);
XenForo_Application::set('page_start_time', $startTime);

$deps = new XenForo_Dependencies_Public();
$deps->preLoadData();

$response = new Zend_Controller_Response_Http();
$receiver = new ThreemaGateway_Handler_Action_Receiver();
$receiver->initCallbackHandling(new Zend_Controller_Request_Http());

$logExtra   = [];
$logMessage = false;

try {
    if (!$receiver->validateRequest($logMessage)) {
        $logType = 'error';

        $response->setHttpResponseCode(500);
    } elseif (!$receiver->validatePreConditions($logMessage)) {
        $logType = 'error';
    } else {
        $logType    = 'info';
        $logMessage = $receiver->processMessage();
    }

    if (is_array($logMessage)) {
        $temp                       = $logMessage;
        list($logType, $logMessage) = $temp;
    }
} catch (Exception $e) {
    $response->setHttpResponseCode(500);
    XenForo_Error::logException($e);

    $logType        = 'error';
    $logMessage     = 'Exception: ' . $e->getMessage();
    $logExtra['_e'] = $e;
}

// debug: write log file
const fpath='internal_data/threemagateway.log';
date_default_timezone_set('Europe/Berlin');

$fhandle=fopen(fpath, 'a');
fwrite($fhandle, '['.date('Y-m-d H:i:s').']'.PHP_EOL.$logMessage.PHP_EOL);
fclose($fhandle);

$logMessage = str_replace(PHP_EOL, '<br />'.PHP_EOL, $logMessage);

$response->setBody($logMessage);
// $response->setBody(htmlspecialchars($logMessage));
$response->sendResponse();

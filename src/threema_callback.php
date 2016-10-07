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
chdir($fileDir);

require($fileDir . '/library/XenForo/Autoloader.php');
XenForo_Autoloader::getInstance()->setupAutoloader($fileDir . '/library');

XenForo_Application::initialize($fileDir . '/library', $fileDir);
XenForo_Application::set('page_start_time', $startTime);

$deps = new XenForo_Dependencies_Public();
$deps->preLoadData();

$response = new Zend_Controller_Response_Http();
$receiver = new ThreemaGateway_Handler_Action_Receiver();
$receiver->initCallbackHandling(new Zend_Controller_Request_Http());

/* @var XenForo_Options */
$options = XenForo_Application::getOptions();
$debugMode = XenForo_Application::debugMode();

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
        $logMessage = $receiver->processMessage(
            $options->threema_gateway_downloadpath,
            ($options->threema_gateway_logreceivedmsgs['enabled'] && $debugMode)
        );
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
if ($options->threema_gateway_logreceivedmsgs['enabled'] && $debugMode) {
    try {
        // check & create dir
        if (!ThreemaGateway_Handler_Validation::checkDir(dirname($options->threema_gateway_logreceivedmsgs['path']))) {
            throw new XenForo_Exception('Download dir ' . dirname($options->threema_gateway_logreceivedmsgs['path']) . ' cannot be accessed.');
        }

        $logheader  = '[' . date('Y-m-d H:i:s') . ']' . PHP_EOL;
        $logheader .= 'UA: ' . $_SERVER['HTTP_USER_AGENT'] . PHP_EOL;

        $fhandle = fopen($options->threema_gateway_logreceivedmsgs['path'], 'a');
        fwrite($fhandle, $logheader . $logMessage . PHP_EOL);
        if ($logExtra) {
            fwrite($fhandle, PHP_EOL . var_export($logExtra, true) . PHP_EOL);
        }
        fclose($fhandle);
    } catch (Exception $e) {
        XenForo_Error::logException($e);
        $logMessage .= PHP_EOL . ' Error when trying to write log file: ' . $e->getMessage();
    }
}

$response->setBody(htmlspecialchars($logMessage));
$response->setHeader('Content-type', 'text/plain');
$response->sendResponse();

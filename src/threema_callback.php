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
$callback = new ThreemaGateway_Handler_Action_Callback();
$callback->initCallbackHandling(new Zend_Controller_Request_Http());

/* @var XenForo_Options */
$options = XenForo_Application::getOptions();
/* @var bool whether XenForo is running in debug mode */
$debugMode = XenForo_Application::debugMode();
// could use ThreemaGateway_Handler_Settings->isDebug() here, but that would
// not be good here as we really only need the RAW debug mode setting.

$logExtra         = [];
$logMessage       = false;
$logMessagePublic = false;

/* test end */

try {
    if (!$callback->validatePreConditions($logMessage)) {
        $logType = 'error';

        // 200 error code = ignore errors
    } elseif (!$callback->validateRequest($logMessage)) {
        $logType = 'error';

        // on security error, let Gateway Server retry
        $response->setHttpResponseCode(500);
    } elseif (!$callback->validateFormalities($logMessage)) {
        $logType = 'error';

        // 200 error code = ignore errors
    } else {
        $logType    = 'info';
        $logMessage = $callback->processMessage(
            $options->threema_gateway_downloadpath,
            ($options->threema_gateway_logreceivedmsgs['enabled'] && $debugMode)
        );
    }

    // split log message if neccessary
    if (is_array($logMessage)) {
        $logTypeBackup                                 = $logType;
        $logMessageBackup                              = $logMessage;
        list($logType, $logMessage, $logMessagePublic) = $logMessageBackup;

        if (!$logType) {
            $logType = $logTypeBackup;
        }
    }
} catch (Exception $e) {
    $response->setHttpResponseCode(500);
    XenForo_Error::logException($e);

    $logType               = 'error';
    $logMessage            = 'Exception: ' . $e->getMessage();
    $logMessagePublic      = 'Message cannot be processed';
    $logExtra['exception'] = $e;
}

// debug: write log file
if ($options->threema_gateway_logreceivedmsgs['enabled'] && $debugMode) {
    try {
        $logheader  = PHP_EOL;
        $logheader .= '[' . date('Y-m-d H:i:s', XenForo_Application::$time) . ']' . PHP_EOL;
        $logheader .= 'UA: ' . $_SERVER['HTTP_USER_AGENT'] . PHP_EOL;
        $logheader .= 'time: ' . sprintf('%f', microtime(true) - $startTime) . 's' . PHP_EOL;

        $fhandle = fopen($options->threema_gateway_logreceivedmsgs['path'], 'a');
        fwrite($fhandle, $logheader . $logMessage . PHP_EOL);
        if ($logExtra) {
            fwrite($fhandle, PHP_EOL . var_export($logExtra, true) . PHP_EOL);
        }
        fclose($fhandle);
    } catch (Exception $e) {
        XenForo_Error::logException($e);
        $logMessage .= PHP_EOL . 'Error when trying to write log file: ' . $e->getMessage();
        $logMessagePublic .= PHP_EOL . 'Error when trying to write log file.';
    }
}

// only show details (which could be useful for an attacker) if no public-only
// information is available
if (!is_string($logMessagePublic)) {
    $logMessagePublic = $logMessage;
}

$response->setBody(htmlspecialchars($logMessagePublic));
$response->setHeader('Content-type', 'text/plain');
$response->sendResponse();

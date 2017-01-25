<?php
/**
 * Callback for Threema Gateway.
 *
 * Inspired by the PayPal Callback included in XenForo.
 * You can remove this file if you uninstalled the Threema Gateway add-on.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

/** @var int $startTime time of application start */
$startTime = microtime(true);
/** @var string $fileDir current dir */
$fileDir = dirname(__FILE__);
if (!file_exists($fileDir . '/library/XenForo/Autoloader.php')) {
    // second try
    $fileDir = '.';
}
chdir($fileDir);

require($fileDir . '/library/XenForo/Autoloader.php');
XenForo_Autoloader::getInstance()->setupAutoloader($fileDir . '/library');

XenForo_Application::initialize($fileDir . '/library', $fileDir);
XenForo_Application::set('page_start_time', $startTime);

/** @var XenForo_Dependencies_Public $deps */
$deps = new XenForo_Dependencies_Public();
$deps->preLoadData();

/** @var Zend_Controller_Response_Http $response */
$response = new Zend_Controller_Response_Http();
/** @var ThreemaGateway_Handler_Action_Callback $callback */
$callback = new ThreemaGateway_Handler_Action_Callback();
$callback->initCallbackHandling(new Zend_Controller_Request_Http());

/** @var XenForo_Options $options */
$options = XenForo_Application::getOptions();
/** @var bool $debugMode whether XenForo is running in debug mode */
$debugMode = XenForo_Application::debugMode();
// could use ThreemaGateway_Handler_Settings->isDebug() here, but that would
// not be good here as we really only need the RAW debug mode setting.

/** @var Exception|null $logException */
$logException = null;
/** @var null|string $logMessage */
$logMessage = null;
/** @var null|string $logMessagePublic */
$logMessagePublic = null;

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

    // split log message if necessary
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
    $logException          = $e;
}

// debug: write log file
if ($options->threema_gateway_logreceivedmsgs['enabled'] && $debugMode) {
    try {
        $logheader  = PHP_EOL;
        $logheader .= '[' . date('Y-m-d H:i:s', XenForo_Application::$time) . ']' . PHP_EOL;
        $logheader .= 'UA: ' . substr($_SERVER['HTTP_USER_AGENT'], 0, 100) . PHP_EOL;
        $logheader .= 'time: ' . sprintf('%f', microtime(true) - $startTime) . 's' . PHP_EOL;

        $fhandle = fopen($options->threema_gateway_logreceivedmsgs['path'], 'a');
        fwrite($fhandle, strip_tags($logheader . $logMessage . PHP_EOL));
        if ($logException) {
            fwrite(
                $fhandle,
                PHP_EOL . strip_tags(get_class($logException) . ' - ' . $logException) . PHP_EOL
            );
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

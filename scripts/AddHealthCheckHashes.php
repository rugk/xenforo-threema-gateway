<?php
/**
 * Creates XenForo health check hashes.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

define('FILE_EXTENSIONS', [
    '.php',
    '.js',
]);
define('FILE_PATH', 'Helper/FileSums.php');
define('FILE_CLASS', 'ThreemaGateway_Helper_FileSums');
define('FILE_INTRO',
'<?php
/**
 * Automatically generated file containing the hash sums
 * for the file health check.
 *
 * This file was generated at ' . date('Y-m-d H:i:s') . '.
 *
 * @package ThreemaGateway
 */

');

/** @var int $startTime time of application start */
$startTime = microtime(true);
/** @var string $xenForoDir dir of XenForo installation */
$xenForoDir = getenv('XENFORO_DIR');
chdir($xenForoDir);

require($xenForoDir . '/library/XenForo/Autoloader.php');
XenForo_Autoloader::getInstance()->setupAutoloader($xenForoDir . '/library');

XenForo_Application::initialize($xenForoDir . '/library', $xenForoDir);
XenForo_Application::set('page_start_time', $startTime);

//get/verify arguments
if (empty($argv[1]) || !file_exists(empty($argv[1]))) {
    throw new XenForo_Exception('Missing or incorrect first parameter.');
}

// get hashes
/** @var array $hashes list of file hashes */
$hashes = XenForo_Helper_Hash::hashDirectory($argv[1], FILE_EXTENSIONS);

/** @var string $hashCode code generated out of hashes */
$hashCode = XenForo_Helper_Hash::getHashClassCode(FILE_CLASS, $hashes);

// add own intro to code
str_replace('<?php', FILE_INTRO, $hashCode);

$fp = fopen($argv[1] . FILE_PATH, 'w');
fwrite($fp, $hashCode);
fclose($fp);

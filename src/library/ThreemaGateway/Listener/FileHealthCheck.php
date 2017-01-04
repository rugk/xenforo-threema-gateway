<?php
/**
 * XenForo health check.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

/**
 * Contains health check information for Threema Gateway.
 */
class ThreemaGateway_Listener_FileHealthCheck
{
    /**
     * Adds own file hashes to XenForos health check.
     *
     * @param object $controller
     * @param array  $hashes
     *
     * @return array $hashes
     */
    public static function fileHealthCheck(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
    {
        // check whether class with file hashes exists, if not this file is already invalid
        if (!class_exists('ThreemaGateway_Helper_FileSums')) {
            // if class is not loadable, mark the file as invalid (with a wrong faked 'hash' here)
            $hashes['ThreemaGateway/Helper/FileSums.php'] = '0';
            // and show another error message for a detailed explanation
            $hashes['ThreemaGateway: The integrity of the Threema Gateway add-on could not be checked.'] = '0';
            return;
        }

        // add hashes from file generated at built time
        $hashes = array_merge($hashes, ThreemaGateway_Helper_FileSums::getHashes());
    }
}

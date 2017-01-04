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
        // pass to real hashes file generated at built time
        array_merge($hashes, ThreemaGateway_Helper_FileSums::getHashes());
    }
}

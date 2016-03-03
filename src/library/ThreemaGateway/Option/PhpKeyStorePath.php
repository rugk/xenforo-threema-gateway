<?php
/**
 * Private key path option.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

class ThreemaGateway_Option_PhpKeyStorePath
{
    /**
     * Verifies the existence of the path.
     *
     * @param string             $phpKeystore Input
     * @param XenForo_DataWriter $dw
     * @param string             $fieldName Name of field/option
     *
     * @return bool
     */
    public static function verifyOption(&$phpKeystore, XenForo_DataWriter $dw, $fieldName)
    {
        if (!$phpKeystore || !$phpKeystore['enabled']) {
            // skip check if PHP keystore is not enabled
            return true;
        }

        // check existence
        if (pathinfo($phpKeystore['path'], PATHINFO_EXTENSION) != 'php' ||
            !file_exists(__DIR__ . '/../' . $phpKeystore['path'])
        ) {
            $dw->error(new XenForo_Phrase('threemagw_invalid_keystorepath'), $fieldName);
            return false;
        }

        // check whether it is writable
        if (!is_writable(__DIR__ . '/../' . $phpKeystore['path'])) {
            $dw->error(new XenForo_Phrase('threemagw_not_writable_keystorefile'), $fieldName);
            return false;
        }

        return true;
    }
}

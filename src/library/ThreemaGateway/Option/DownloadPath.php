<?php
/**
 * Private key path option.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

class ThreemaGateway_Option_DownloadPath
{
    /**
     * Verifies whether the dir is valid (can be created) and is writable.
     *
     * @param string             $dirpath  Input
     * @param XenForo_DataWriter $dw
     * @param string             $fieldName Name of field/option
     *
     * @return bool
     */
    public static function verifyOption(&$dirpath, XenForo_DataWriter $dw, $fieldName)
    {
        // correct path
        if (substr($dirpath, 0, 1) == '/') {
            $dirpath = substr($dirpath, 1);
        }

        // check path
        $absoluteDir = XenForo_Application::getInstance()->getRootDir() . '/' . $dirpath;
        var_dump($absoluteDir);
        if ($dirpath != '' &&
            !ThreemaGateway_Handler_Validation::checkDir($absoluteDir)
        ) {
            $dw->error(new XenForo_Phrase('threemagw_invalid_downloadpath'), $fieldName);
            return false;
        }

        return true;
    }
}

<?php
/**
 * Private key path option.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

class ThreemaGateway_Option_DebugModeLog
{
    /**
     * Verifies whether the dir of the file is valid (can be created) and is writable.
     *
     * @param string             $filepath  Input
     * @param XenForo_DataWriter $dw
     * @param string             $fieldName Name of field/option
     *
     * @return bool
     */
    public static function verifyOption(&$filepath, XenForo_DataWriter $dw, $fieldName)
    {
        // correct value
        if (empty($filepath)) {
            /* @var XenForo_Options */
            $options = XenForo_Application::getOptions();

            // save file path even if disabled
            $filepath['enabled'] = 0;
            $filepath['path'] = $options->threema_gateway_logreceivedmsgs['path'];
        }

        // correct path
        if (substr($filepath['path'], 0, 1) == '/') {
            $filepath['path'] = substr($filepath['path'], 1);
        }
        $dirpath = $filepath['path'];

        // check path
        $absoluteDir = XenForo_Application::getInstance()->getRootDir() . '/' . $dirpath;
        var_dump($absoluteDir);
        if ($dirpath != '' &&
            !ThreemaGateway_Handler_Validation::checkDir($absoluteDir)
        ) {
            $dw->error(new XenForo_Phrase('threemagw_invalid_debuglogpath'), $fieldName);
            return false;
        }

        // auto-remove existing file if disabled
        if (!$filepath['enabled'] && file_exists($filepath['path'])) {
            unlink(realpath($filepath['path']));
        }

        return true;
    }
}

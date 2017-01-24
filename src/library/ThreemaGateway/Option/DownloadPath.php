<?php
/**
 * Download path option.
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
     * @param string             $dirpath    Input
     * @param XenForo_DataWriter $dataWriter
     * @param string             $fieldName  Name of field/option
     *
     * @return bool
     */
    public static function verifyOption(&$dirpath, XenForo_DataWriter $dataWriter, $fieldName)
    {
        // correct path
        if (substr($dirpath, 0, 1) == '/') {
            $dirpath = substr($dirpath, 1);
        }

        // if disabled (path = empty), we accept this
        if ($dirpath == '') {
            return true;
        }

        $absoluteDir = XenForo_Application::getInstance()->getRootDir() . '/' . $dirpath;

        // try to move the old dir to the new place
        try {
            self::moveDir($absoluteDir);
        } catch (Exception $e) {
            // ignore me, moving the dir is not critical
        }

        // check path
        if (!ThreemaGateway_Handler_Validation::checkDir($absoluteDir)) {
            $dataWriter->error(new XenForo_Phrase('threemagw_invalid_downloadpath'), $fieldName);
            return false;
        }

        return true;
    }


    /**
     * Moves old dir to new path if useful/possible.
     *
     * When the dir was not moved for some reason, it returns false. If a move
     * was done, it returns true. This means it silently fails, if the dir could
     * not be moved.
     *
     * @param  string    $newDir Please pass the absolute path here!
     * @throws Exception (from rename())
     * @return bool
     */
    protected static function moveDir($newDir)
    {
        /** @var XenForo_Options $xenOptions */
        $xenOptions = XenForo_Application::getOptions();
        /** @var string $orgDir original old dir */
        $orgDir = XenForo_Application::getInstance()->getRootDir() . '/' . $xenOptions->threema_gateway_downloadpath;

        // if no option set or previous option does not apply anymore
        // there is nothing to do
        if (empty($xenOptions->threema_gateway_downloadpath) ||
            !file_exists($orgDir)) {
            return false;
        }

        // if old dir check fails, do not go on
        if (!ThreemaGateway_Handler_Validation::checkDir($orgDir)) {
            return false;
        }

        return rename($orgDir, $newDir);
    }
}

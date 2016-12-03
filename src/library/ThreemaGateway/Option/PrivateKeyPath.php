<?php
/**
 * Private key path option.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

class ThreemaGateway_Option_PrivateKeyPath
{
    /**
     * Verifies the existence of the path.
     *
     * Note that $filepath can also be the key directly. However this is neither
     * the official way nor recommend/documented.
     *
     * @param string             $filepath  Input
     * @param XenForo_DataWriter $dw
     * @param string             $fieldName Name of field/option
     *
     * @return bool
     */
    public static function verifyOption(&$filepath, XenForo_DataWriter $dw, $fieldName)
    {
        // correct path
        if (substr($filepath, 0, 1) == '/') {
            $filepath = substr($filepath, 1);
        }

        // check path
        if ($filepath != '' && //ignore empty field
            !file_exists(__DIR__ . '/../' . $filepath) && //verify accessibility
            !ThreemaGateway_Helper_Key::check($filepath, 'private:') //or that it's the key directly
        ) {
            $dw->error(new XenForo_Phrase('threemagw_invalid_privkeypath'), $fieldName);
            return false;
        }

        return true;
    }
}

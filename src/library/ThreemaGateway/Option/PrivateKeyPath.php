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
        $filepath = self::correctOption($filepath);

        // check path
        if ($filepath != '' && //ignore/allow empty field
            !self::isValidPath($filepath) && //verify path
            !ThreemaGateway_Helper_Key::check($filepath, 'private:') //allow private key string directly
        ) {
            $dw->error(new XenForo_Phrase('threemagw_invalid_privkeypath'), $fieldName);
            return false;
        }

        return true;
    }

    /**
     * Remove the log file.
     *
     * @param  array $filepath option setting
     * @return bool
     */
    public static function removePrivateKey($filepath)
    {
        // to be sure check the path again
        $filepath = self::correctOption($filepath);

        // check pre-conditions
        if (!self::isValidPath($filepath)) {
            return false;
        }

        // remove file
        return unlink(realpath(__DIR__ . '/../' . $filepath));
    }

    /**
     * Corrects the option file path.
     *
     * @param  string $filepath
     * @return bool
     */
    protected static function isValidPath($filepath)
    {
        return (
            $filepath != '' && // empty strings can never be a valid path
            file_exists(__DIR__ . '/../' . $filepath) //verify accessibility
        );
    }

    /**
     * Corrects the option file path.
     *
     * @param  string $filepath
     * @return string
     */
    protected static function correctOption($filepath)
    {
        // correct path
        if (substr($filepath, 0, 1) == '/') {
            $filepath = substr($filepath, 1);
        }

        return $filepath;
    }
}

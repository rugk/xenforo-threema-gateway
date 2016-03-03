<?php
/**
 * Threema Gateway ID option.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

class ThreemaGateway_Option_ThreemaGatewayId
{
    /**
     * Verifies the Threema ID format.
     *
     * @param string             $threemaid Input threema ID
     * @param XenForo_DataWriter $dw
     * @param string             $fieldName Name of field/option
     *
     * @return bool
     */
    public static function verifyOption(&$threemaid, XenForo_DataWriter $dw, $fieldName)
    {
        //check for formal errors
        if ($threemaid != '' && !preg_match('/' . ThreemaGateway_Constants::RegExThreemaId['gateway'] . '/', $threemaid)) {
            $dw->error(new XenForo_Phrase('threemagw_invalid_threema_id'), $fieldName);
            return false;
        }

        return true;
    }
}

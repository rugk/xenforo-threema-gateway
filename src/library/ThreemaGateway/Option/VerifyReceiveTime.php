<?php
/**
 * Option, which sets whether the receive time should be verified and if set,
 * what time span should be allowed.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

class ThreemaGateway_Option_VerifyReceiveTime
{
    /**
     * Verifies the existence of the path.
     *
     * @param string             $option Input
     * @param XenForo_DataWriter $dw
     * @param string             $fieldName   Name of field/option
     *
     * @return bool
     */
    public static function verifyOption(&$option, XenForo_DataWriter $dw, $fieldName)
    {
        if (!$option || !$option['enabled']) {
            // skip check if option is disabled
            return true;
        }

        // disable option if no time is given
        if (!$option['time']) {
            $option['enabled'] = 0;
            return true;
        }

        // auto-correct to add - at start to make it point in the past
        if (substr($option['time'], 0, 1) !== '-') {
            $option['time'] = '-' . $option['time'];
        }

        $test = strtotime($option['time']);
        if (!$test ||
            $test > strtotime('now')
        ) {
            $dw->error(new XenForo_Phrase('threemagw_invalid_discard_old_date'), $fieldName);
            return false;
        }

        return true;
    }
}

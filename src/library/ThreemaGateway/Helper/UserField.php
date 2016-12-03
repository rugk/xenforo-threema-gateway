<?php
/**
 * User field helper.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
*/

/**
 * Verifies user fields.
 */
class ThreemaGateway_Helper_UserField
{
    /**
     * Checks the custom Threema ID user field.
     *
     * This basically only passes the request to
     * ThreemaGateway_Handler_Verification->checkThreemaId().
     *
     * @param  string $field The field id
     * @param  string $value The entered value
     * @param  mixed  $error
     * @return bool
     */
    public static function verifyThreemaId($field, &$value, &$error)
    {
        /** @var XenForo_Options $options */
        $options = XenForo_Application::getOptions();

        return ThreemaGateway_Handler_Validation::checkThreemaId(
            $value, 'personal', $error, $options->threema_gateway_userfield_verifyexist
        );
    }
}

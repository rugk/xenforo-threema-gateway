<?php
/**
 * User field helper
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
*/

/**
 * Verifies user fields
 */
class ThreemaGateway_Helper_UserField
{
    /**
     * Checks the custom Threema ID user field.
     *
     * This basically only forwards the request to
     * ThreemaGateway_Handler->checkThreemaId().
     *
     * @param string $field The field id
     * @param string $value The entered value
     * @param array $error
     * @return bool
     */
    public static function verifyThreemaId($field, &$value, &$error) {
        /** @var ThreemaGateway_Handler $gatewayHandler */
        $gatewayHandler = new ThreemaGateway_Handler;
        return $gatewayHandler->checkThreemaId($value, 'personal', $error);
    }
}

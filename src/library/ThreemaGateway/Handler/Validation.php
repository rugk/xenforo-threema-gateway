<?php
/**
 * Validate different things.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

class ThreemaGateway_Handler_Validation
{
    /**
     * Checks whether a Threema ID is valid ande exists.
     *
     * @param  string $threemaid      The Threema ID to check.
     * @param  string $type           The type of the Threema ID (personal, gateway, any)
     * @param  XenForo_Phrase $error
     * @param  bool   $checkExistence Whether not only formal aspects should
     *                                be checked, but also the existence of the ID.
     * @return bool
     */
    public static function checkThreemaId(&$threemaid, $type, &$error, $checkExistence = true)
    {
        $threemaid = strtoupper($threemaid);

        // check whether an id is formally correct
        if (!preg_match('/' . ThreemaGateway_Constants::RegExThreemaId[$type] . '/', $threemaid)) {
            $error = new XenForo_Phrase('threemagw_invalid_threema_id');
            return false;
        }

        if (!$checkExistence) {
            return true;
        }

        /** @var ThreemaGateway_Handler_GatewayServer $gatewayHandlerServer */
        $gatewayHandlerServer = new ThreemaGateway_Handler_GatewayServer;

        // fetches public key of an id to check whether it exists
        try {
            /** @var string $publicKey */
            $publicKey = $gatewayHandlerServer->fetchPublicKey($threemaid);
        } catch (Exception $e) {
            $error = new XenForo_Phrase('threemagw_threema_id_does_not_exist');
            return false;
        }

        return true;
    }
}

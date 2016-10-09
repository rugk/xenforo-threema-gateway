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
     * Checks whether a Threema ID is valid and exists.
     *
     * @param  string         $threemaid      The Threema ID to check.
     * @param  string         $type           The type of the Threema ID (personal, gateway, any)
     * @param  XenForo_Phrase $error
     * @param  bool           $checkExistence Whether not only formal aspects should
     *                                        be checked, but also the existence of the ID.
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

        /** @var ThreemaGateway_Handler_Action_GatewayServer $gwServer */
        $gwServer = new ThreemaGateway_Handler_Action_GatewayServer;

        // fetches public key of an id to check whether it exists
        try {
            /** @var string $publicKey */
            $publicKey = $gwServer->fetchPublicKey($threemaid);
        } catch (Exception $e) {
            // to show detailed error messages: $error = new XenForo_Phrase('threemagw_threema_id_does_not_exist') . ' ' . $e->getMessage();
            $error = new XenForo_Phrase('threemagw_threema_id_does_not_exist');
            return false;
        }

        return true;
    }

    /**
     * Checks whether the directory is read- and writable.
     * It also automatically creates it if neccessary.
     *
     * @param  string $dir directory to check
     * @return bool
     */
    public static function checkDir($dir)
    {
        if (!file_exists($dir)) {
            try {
                mkdir($dir, 0770, true);
            } catch (Exception $e) {
                return false;
            }
        }
        return (is_readable($dir) && is_writable($dir));
    }
}

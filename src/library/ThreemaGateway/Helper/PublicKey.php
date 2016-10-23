<?php
/**
 * Extent init_dependencies with template helpers for public key actions.
 * Generally this provides an helper interface for ThreemaGateway_Handler_Key.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Helper_PublicKey
{
    /**
     * XenForo template helper: threemaidpubkey.
     *
     * Returns the public key of a Threema ID.
     *
     * @param  string            $threemaid Threema ID
     * @throws XenForo_Exception
     * @return string
     */
    public static function get($threemaid)
    {
        if ($threemaid == '') {
            return '';
        }

        /** @var ThreemaGateway_Handler_Action_GatewayServer */
        $gatewayHandlerServer = new ThreemaGateway_Handler_Action_GatewayServer;

        try {
            $publicKey = $gatewayHandlerServer->fetchPublicKey($threemaid);
        } catch (Exception $e) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_threema_id_does_not_exist') . ' ' . $e->getMessage());
        }

        return $publicKey;
    }

    /**
     * XenForo template helper: threemaidpubkeyshort.
     *
     * Returns the short (user friendly) hash of the public key of a Threema ID.
     * Do not confuse this with {@link convertShort()}. Here you have to pass
     * the Threema Id!
     *
     * @param  string $threemaid Threema ID
     * @return string
     */
    public static function getShort($threemaid)
    {
        if ($threemaid == '') {
            return '';
        }
        return self::convertShort(self::get($threemaid));
    }

    /**
     * XenForo template helper: threemashortpubkey.
     *
     * Returns the short (user friendly) hash of the public key. Do not confuse
     * this with {@link getShort()}. Here you have to pass the public key!
     *
     * @param  string $pubKey Long public key
     * @return string
     */
    public static function convertShort($pubKey)
    {
        return ThreemaGateway_Handler_Key::getUserDisplay($pubKey);
    }

    /**
     * XenForo template helper: threemaispubkey.
     *
     * Checks whether a public key is valid.
     *
     * @param  string $pubKey Long public key
     * @return bool
     */
    public static function check($pubKey, $suffix = 'public:')
    {
        return ThreemaGateway_Handler_Key::check($pubKey, $suffix);
    }

    /**
     * XenForo template helper: threemapubkeyremovesuffix.
     *
     * Removes the suffix if neccessary.
     *
     * @param  string $pubKey Long public key
     * @return string
     */
    public static function removeSuffix($pubKey)
    {
        return ThreemaGateway_Handler_Key::removeSuffix($pubKey);
    }
}

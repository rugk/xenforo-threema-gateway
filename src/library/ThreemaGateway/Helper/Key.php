<?php
/**
 * Private/public key operations.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Helper_Key
{
    /**
     * Returns the public key of a Threema ID.
     *
     * XenForo template helper: threemaidpubkey.
     *
     * @param  string            $threemaid Threema ID
     * @throws XenForo_Exception
     * @return string
     */
    public static function getPublic($threemaid)
    {
        if ($threemaid == '') {
            return '';
        }

        /** @var ThreemaGateway_Handler_Action_GatewayServer $gatewayHandlerServer */
        $gatewayHandlerServer = new ThreemaGateway_Handler_Action_GatewayServer;

        try {
            $publicKey = $gatewayHandlerServer->fetchPublicKey($threemaid);
        } catch (Exception $e) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_threema_id_does_not_exist') . ' ' . $e->getMessage());
        }

        return $publicKey;
    }

    /**
     * Returns the short (user friendly) hash of the public key of a Threema ID.
     *
     * XenForo template helper: threemaidpubkeyshort.
     * Do not confuse this with {@link getUserDisplay()}. Here you have to pass
     * the Threema Id!
     *
     * @param  string $threemaid Threema ID
     * @return string
     */
    public static function getPublicShort($threemaid)
    {
        if ($threemaid == '') {
            return '';
        }
        return self::getUserDisplay(self::getPublic($threemaid));
    }

    /**
     * Checks whether a public key is valid.
     *
     * XenForo template helper: threemaisvalidpubkey.
     *
     * @param  string $threemaid Threema ID
     * @return string
     */
    public static function checkPublic($pubKey)
    {
        return self::check($pubKey, 'public:');
    }

    /**
     * Checks whether a HEX-key is valid.
     *
     * XenForo template helper: threemaisvalidkey.
     *
     * @param string the public key
     * @param string optional suffix (usually 'private:' or 'public:') (default: '')
     *
     * @return bool whether the key is valid (true) or not (false)
     */
    public static function check($publicKey, $suffix = '')
    {
        // RegExp: https://regex101.com/r/sU5tC8/1
        return preg_match('/^(' . $suffix . ')?[[:alnum:]]{64}$/', $publicKey);
    }

    /**
     * Returns the short user-friendly hash of the public key.
     *
     * XenForo template helper: threemashortpubkey.
     * Do not confuse this with {@link getPublicShort()}. Here you have to pass
     * the public key!
     * This is the way the key is also displayed in the Threema app. For example
     * ECHOECHO is shown as `d30f795a904a213578baecc62c8611b5`. However the
     * full public key of it is:
     * `4a6a1b34dcef15d43cb74de2fd36091be99fbbaf126d099d47d83d919712c72b`
     *
     * @param string $publicKey The public key to format.
     *
     * @return string 32 hex characters
     */
    public static function getUserDisplay($publicKey)
    {
        //force key to be binary
        if (ctype_alnum($publicKey)) {
            $keyConverter = new ThreemaGateway_Handler_Action_KeyConverter;
            $publicKey    = $keyConverter->hexToBin($publicKey);
        }

        //create and return short hash
        return substr(hash('sha256', $publicKey), 0, 32);
    }

    /**
     * Removes the suffix if neccessary.
     *
     * XenForo template helper: threemakeyremovesuffix.
     *
     * @param  string $keyHex The key in hex
     * @return string
     */
    public static function removeSuffix($keyHex)
    {
        /** @var string $keyTypeCheck */
        $keyTypeCheck = substr($keyHex, 0, 8);
        if ($keyTypeCheck == 'private:' || $keyTypeCheck == 'public:') {
            $keyHex = substr($keyHex, 8);
        }

        return $keyHex;
    }
}

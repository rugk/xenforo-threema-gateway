<?php
/**
 * Private/Public Key operations.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Handler_Key
{
    /**
     * Checks whether a HEX-key is valid.
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
            $publicKey = self::hexToBin($publicKey);
        }

        //create and return short hash
        return substr(hash('sha256', $publicKey), 0, 32);
    }

    /**
     * Converts a key from hex (string) to binary format.
     *
     * It automatically removes the prefixes if neccessary.
     *
     * @param  string $keyHex The key in hex
     * @return string
     */
    public static function hexToBin($keyHex)
    {
        //delete suffix
        $keyHex = self::removeSuffix($keyHex);

        //convert key
        if (ThreemaGateway_Handler_Libsodium::canUse()) {
            /** @var ThreemaGateway_Handler_Libsodium $libsodiumHelper */
            $libsodiumHelper = new ThreemaGateway_Handler_Libsodium;
            /** @var string $keyBin */
            $keyBin = $libsodiumHelper->hex2bin($keyHex);
        } else {
            /** @var string $keyBin */
            $keyBin = hex2bin($keyHex);
        }

        return $keyBin;
    }

    /**
     * Removes the suffix if neccessary.
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

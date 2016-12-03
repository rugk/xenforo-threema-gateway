<?php
/**
 * Private/Public key convertion.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Handler_Action_KeyConverter extends ThreemaGateway_Handler_Action_Abstract
{
    /**
     * Converts a key from hex to binary format.
     *
     * It automatically removes the prefixes if neccessary.
     *
     * @param  string $keyHex The key in hex
     * @return string
     */
    public function hexToBin($keyHex)
    {
        //delete suffix
        $keyHex = ThreemaGateway_Helper_Key::removeSuffix($keyHex);

        return $this->getCryptTool()->hex2bin($keyHex);
    }

    /**
     * Converts a key from binary to hex format.
     *
     * @param  string $keyBin The key in binary format
     * @return string
     */
    public function binToHex($keyBin)
    {
        return $this->getCryptTool()->bin2hex($keyBin);
    }

    /**
     * Converts a key from a private key to a public key version.
     *
     * @param  string $privateKey The private key in hex
     * @return string public key in hex
     */
    public function derivePublicKey($privateKey)
    {
        return $this->binToHex($this->getCryptTool()->derivePublicKey($this->hexToBin($privateKey)));
    }
}

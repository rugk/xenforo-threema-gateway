<?php
/**
 * Keystore helper. Chooses the correct keystore.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

/**
 * Imitates a real PHP-SDK keystore, but only acts as a redirector to another keystore.
 */
class ThreemaGateway_Handler_Keystore extends Threema\MsgApi\PublicKeyStore
{
    /**
     * @var ThreemaGateway_Model_Keystore keystore to redirect to
     */
    private $keystore;

    /**
     * Initialises the key store.
     *
     * @return PhpFile
     */
    public function __construct()
    {
        /** @var XenForo_Options */
        $options = XenForo_Application::get('options');
        /** @var array The setting for an optional PHP keystore */
        $phpKeystore = $options->threema_gateway_keystorefile;

        if (!$phpKeystore || !$phpKeystore['enabled']) {
            $this->keystore = new ThreemaGateway_Model_Keystore();
        } else {
            $this->keystore = new Threema\MsgApi\PublicKeyStores\PhpFile(__DIR__ . '/../' . $phpKeystore['path']);
        }
    }

    /**
     * Find public key. Returns null if the public key not found in the store.
     *
     * @param  string      $threemaId
     * @return null|string
     */
    public function findPublicKey($threemaId)
    {
        return $this->keystore->findPublicKey($threemaId);
    }

    /**
     * Save a public key.
     *
     * @param  string    $threemaId
     * @param  string    $publicKey
     * @throws Exception
     * @return bool
     */
    public function savePublicKey($threemaId, $publicKey)
    {
        return $this->keystore->savePublicKey($threemaId, $publicKey);
    }

    /**
     * Initialize a new PhpFile Public Key Store.
     *
     * @param  string  $path the file will be created it it does not exist
     * @return PhpFile
     */
    public static function create($path)
    {
        if ($this->keystore instanceof ThreemaGateway_Model_Keystore) {
            return $this->keystore->createKeystore();
        } else {
            return $this->keystore->create($path);
        }
    }
}

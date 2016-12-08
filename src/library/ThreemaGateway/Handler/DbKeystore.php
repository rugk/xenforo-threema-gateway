<?php
/**
 * Splits keystore requests to model and DataWriter.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

/**
 * Initiates a real SDK keystore, but only redirects requests.
 */
class ThreemaGateway_Handler_DbKeystore extends Threema\MsgApi\PublicKeyStore
{
    /**
     * @var ThreemaGateway_Model_Keystore Model to keystore
     */
    private $model;

    /**
     * @var ThreemaGateway_DataWriter_Keystore DataWriter of keystore
     */
    private $dataWriter;

    /**
     * Initialises the key store.
     *
     * @return PhpFile
     */
    public function __construct()
    {
        $this->model      = XenForo_Model::create('ThreemaGateway_Model_Keystore');
        $this->dataWriter = XenForo_DataWriter::create('ThreemaGateway_DataWriter_Keystore');
    }

    /**
     * Find public key. Returns null if the public key not found in the store.
     *
     * @param  string      $threemaId
     * @return null|string
     */
    public function findPublicKey($threemaId)
    {
        return $this->model->findPublicKey($threemaId);
    }

    /**
     * Save a public key.
     *
     * @param  string $threemaId
     * @param  string $publicKey
     * @return bool
     */
    public function savePublicKey($threemaId, $publicKey)
    {
        $this->dataWriter->set('threema_id', $threemaId);
        $this->dataWriter->set('public_key', $publicKey);
        return $this->dataWriter->save();
    }

    /**
     * Ignores requests to create method.
     *
     * @param string $path
     */
    public static function create($path)
    {
        return;
    }
}

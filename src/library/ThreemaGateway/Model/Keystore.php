<?php
/**
 * Model for Threema database keystore.
 * TODO: Split intoi model and DataWriter and adjust.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Model_Keystore extends XenForo_Model
{
    /**
     * @var string database table name
     */
    const DbTable = 'xf_threemagw_keystore';

    /**
     * Initialises the key store.
     */
    public function __construct()
    {
        // Nothing to do...
    }

    /**
     * Find public key. Returns null if the public key not found in the store.
     *
     * @param  string      $threemaId
     * @return null|string
     */
    public function findPublicKey($threemaId)
    {
        /* @var array */
        $result = $this->fetchAllKeyed('SELECT * FROM `' . self::DbTable . '`
                  WHERE `threemaid` = ?',
                  'threemaid',
                  [$threemaId]);

        if (array_key_exists($threemaId, $result)) {
            return (string) $result[$threemaId]['publickey'];
        }

        return null;
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
        $db = $this->_getDb();
        $db->query('INSERT IGNORE INTO `' . self::DbTable . '`
                  (`threemaid`, `publickey`)
                  VALUES (?, ?)',
                  [$threemaId, $publicKey]);

        return true;
    }
}

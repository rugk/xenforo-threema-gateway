<?php
/**
 * Model for Threema keystore.
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
     *
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
        /** @var array */
        $result = $this->fetchAllKeyed("SELECT * FROM `" . self::DbTable . "`
                  WHERE `threemaid` = ?",
                  'threemaid',
                  [$threemaId]);

        if (array_key_exists($threemaId, $result)) {
            return (string)$result[$threemaId]['publickey'];
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
        $db->query("INSERT IGNORE INTO `" . self::DbTable . "`
                  (`threemaid`, `publickey`)
                  VALUES (?, ?)",
                  [$threemaId, $publicKey]);

        return true;
    }

    /**
     * Create a new keystore (table) in the database.
     *
     * This is a replacement of the standard "create" function of keystores,
     * but as create is already defined in XenForo_Model this canot be used
     * here.
     *
     */
    public function createKeystore()
    {
        $db = $this->_getDb();
        $db->query("CREATE TABLE `" . self::DbTable . "`
            (`threemaid` CHAR(8) NOT NULL PRIMARY KEY,
            `publickey` CHAR(64) NOT NULL)
            ");
    }

    /**
     * Deletes the keystore (table).
     *
     * This is a non-standard function for a keystore.
     *
     */
    public function deleteKeystore()
    {
        $db = $this->_getDb();
        $db->query("DROP TABLE `" . self::DbTable . "`");
    }
}

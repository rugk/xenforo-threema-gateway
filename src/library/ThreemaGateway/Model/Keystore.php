<?php
/**
 * Model for Threema database keystore.
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
     * Find public key. Returns null if the public key is not found in the store.
     *
     * @param  string      $threemaId
     * @return null|string
     */
    public function findPublicKey($threemaId)
    {
        /* @var mixed result of SQL query */
        $result = $this->_getDb()->fetchRow('SELECT * FROM `' . self::DbTable . '`
                  WHERE `threemaid` = ?',
                  $threemaId);

        if (is_array($result) && array_key_exists('publickey', $result)) {
            return (string) $result['publickey'];
        }

        return null;
    }
}

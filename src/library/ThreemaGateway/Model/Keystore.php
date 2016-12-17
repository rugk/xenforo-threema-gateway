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
    const DB_TABLE = 'xf_threemagw_keystore';

    /**
     * Find public key. Returns false if the public key is not found in the
     * store.
     *
     * @param string $threemaId
     *
     * @return null|string
     */
    public function findPublicKey($threemaId)
    {
        /** @var mixed $result result of SQL query */
        $result = $this->_getDb()->fetchRow('SELECT * FROM `' . self::DB_TABLE . '`
                  WHERE `threema_id` = ?',
                  $threemaId);

        if (is_array($result) && array_key_exists('public_key', $result)) {
            return (string) $result['public_key'];
        }

        return null;
    }
}

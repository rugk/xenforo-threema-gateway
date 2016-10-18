<?php
/**
 * DataWriter for Threema messages.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

class ThreemaGateway_DataWriter_Messages extends XenForo_DataWriter
{
    /**
     * @var string database table name
     */
    const DbTable = 'xf_threemagw_keystore';

    /**
     * Gets the fields that are defined for the table. See parent for explanation.
     *
     * @return array
     */
    protected function _getFields()
    {
        // return [
        //     self::DbTable => [
        //         'threemaid' => [
        //             'type' => self::TYPE_STRING,
        //             'required'  => true,
        //             'maxLength' => 8
        //         ],
        //         'publickey'    => [
        //             'type' => self::TYPE_STRING,
        //             'required'  => true,
        //             'maxLength' => 64
        //         ],
        //     ]
        // ];
    }

    /**
     * Gets the actual existing data out of data that was passed in. See parent for explanation.
     *
     * As an update cannot happen in the keystore anyway, this function is not
     * implemented in any way.
     *
     * @param mixed
     * @see XenForo_DataWriter::_getExistingData()
     * @return array|false
     */
    protected function _getExistingData($data)
    {
        return false;
    }

    /**
     * Gets SQL condition to update the existing record.
     *
     * As an update cannot happen in the keystore anyway, this function is not
     * implemented in any way.
     *
     * @see XenForo_DataWriter::_getUpdateCondition()
     * @return string
     */
    protected function _getUpdateCondition($tableName)
    {
        return '';
    }

    /**
     * Get the keystore model.
     *
     * @return ThreemaGateway_Model_Keystore
     */
    protected function _getKeystoreModel()
    {
        return $this->getModelFromCache('ThreemaGateway_Model_Keystore');
    }

    /**
     * Save a public key.
     *
     * @deprecated No longer used as real DataWriter is used instead.
     *             Removed in stable.
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

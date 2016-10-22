<?php
/**
 * DataWriter for Threema database keystore.
 *
 * As public keys and Threema IDs are linked together and cannot be changed this
 * DataWriter does not use real _getExistingData or _getUpdateCondition.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

class ThreemaGateway_DataWriter_Keystore extends XenForo_DataWriter
{
    /**
     * @var string database table name
     */
    const DbTable = 'xf_threemagw_keystore';

    /**
     * Gets the fields that are defined for the table. See parent for explanation.
     *
     * @see XenForo_DataWriter::_getFields()
     * @return array
     */
    protected function _getFields()
    {
        return [
            self::DbTable => [
                'threemaid' => [
                    'type' => self::TYPE_STRING,
                    'required'  => true,
                    'maxLength' => 8
                ],
                'publickey'    => [
                    'type' => self::TYPE_STRING,
                    'required'  => true,
                    'maxLength' => 64
                ],
            ]
        ];
    }

    /**
     * Gets the actual existing data out of data that was passed in. See parent for explanation.
     *
     * As an update cannot happen in the keystore anyway, this function is not
     * implemented in any way.
     *
     * @param mixed
     * @see XenForo_DataWriter::_getExistingData()
     * @return array
     */
    protected function _getExistingData($data)
    {
        return [];
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
}

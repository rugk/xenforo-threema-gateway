<?php
/**
 * DataWriter for pending message requests of Threema TFA.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

class ThreemaGateway_DataWriter_TfaPendingMessagesConfirmation extends XenForo_DataWriter
{
    /**
     * Gets the fields that are defined for the table. See parent for explanation.
     *
     * @see XenForo_DataWriter::_getFields()
     * @return array
     */
    protected function _getFields()
    {
        return [
            ThreemaGateway_Model_TfaPendingMessagesConfirmation::DbTable => [
                'request_id' => [
                    'type' => self::TYPE_UINT,
                    'autoIncrement' => true
                ],
                'threema_id' => [
                    'type' => self::TYPE_STRING,
                    'required' => true,
                    'maxLength' => 8
                ],
                'provider_id' => [
                    'type' => self::TYPE_STRING,
                    'required' => true
                ],
                'pending_type' => [
                    'type' => self::TYPE_UINT,
                    'required' => true
                ],
                'user_id' => [
                    'type' => self::TYPE_UINT,
                    'required' => true,
                    'maxLength' => 10
                ],
                'session_id' => [
                    'type' => self::TYPE_STRING,
                    'required' => true
                ],
                'extra_data' => [
                    'type' => self::TYPE_BINARY,
                    'required' => false
                ],
                'expiry_date' => [
                    'type' => self::TYPE_UINT,
                    'required' => true
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
        // try primary key first
        /** @var string $requestId */
        if ($requestId = $this->_getExistingPrimaryKey($data, 'request_id')) {
            return [
                ThreemaGateway_Model_TfaPendingMessagesConfirmation::DbTable =>
                    $this->_getPendingConfirmMsgModel()->getPendingById($requestId)
            ];
        }

        // or use other keys
        if (isset($data[ThreemaGateway_Model_TfaPendingMessagesConfirmation::DbTable]['threema_id'])) {
            /** @var string|null $pendingType */
            $pendingType = null;
            /** @var string|null $providerId */
            $providerId = null;

            if (isset($data[ThreemaGateway_Model_TfaPendingMessagesConfirmation::DbTable]['provider_id'])) {
                $pendingType = $data[ThreemaGateway_Model_TfaPendingMessagesConfirmation::DbTable]['provider_id'];
            }

            if (isset($data[ThreemaGateway_Model_TfaPendingMessagesConfirmation::DbTable]['pending_type'])) {
                $pendingType = $data[ThreemaGateway_Model_TfaPendingMessagesConfirmation::DbTable]['pending_type'];
            }

            /** @var array|null $result database query result */
            $result = $this->_getPendingConfirmMsgModel()->getPending(
                $data[ThreemaGateway_Model_TfaPendingMessagesConfirmation::DbTable]['threema_id'],
                $providerId,
                $pendingType
            );

            if (!$result) {
                return null;
            }

            // as result is keyed we just use the first value here (usually there should
            // only be one value anyway)
            return [
                ThreemaGateway_Model_TfaPendingMessagesConfirmation::DbTable =>
                    current($result)
            ];
        }

        return [];
    }

    /**
     * Gets SQL condition to update the existing record.
     *
     * @see XenForo_DataWriter::_getUpdateCondition()
     * @return string
     */
    protected function _getUpdateCondition($tableName)
    {
        return 'request_id = ' . $this->_db->quote($this->getExisting('request_id'));
    }


    /**
     * Get the pending confirmation messages model.
     *
     * @return ThreemaGateway_Model_TfaPendingMessagesConfirmation
     */
    protected function _getPendingConfirmMsgModel()
    {
        return $this->getModelFromCache('ThreemaGateway_Model_TfaPendingMessagesConfirmation');
    }
}

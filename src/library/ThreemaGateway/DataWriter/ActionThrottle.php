<?php
/**
 * DataWriter for temporare action log.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2017 rugk
 * @license MIT
 */

class ThreemaGateway_DataWriter_ActionThrottle extends XenForo_DataWriter
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
            ThreemaGateway_Model_ActionThrottle::DB_TABLE => [
                'action_id' => [
                    'type' => self::TYPE_UINT,
                    'autoIncrement' => true
                ],
                'user_id' => [
                    'type' => self::TYPE_UINT,
                    'required' => true
                ],
                'action_date' => [
                    'type' => self::TYPE_UINT,
                    'required' => true
                ],
                'action_type' => [
                    'type' => self::TYPE_STRING,
                    'required' => true,
                    'maxLength' => 25
                ],
            ]
        ];
    }

    /**
     * Gets the actual existing data out of data that was passed in. See parent for explanation.
     *
     * As an update cannot happen in the table anyway, this function is not
     * implemented in any way.
     *
     * @param mixed $data
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
     * As an update cannot happen in the table anyway, this function is not
     * implemented in any way.
     *
     * @param string $tableName
     * @see XenForo_DataWriter::_getUpdateCondition()
     * @return string
     */
    protected function _getUpdateCondition($tableName)
    {
        return '';
    }


    /**
     * Get the action throttle model.
     *
     * @return ThreemaGateway_Model_ActionThrottle
     */
    protected function _getActionThrottleModel()
    {
        return $this->getModelFromCache('ThreemaGateway_Model_ActionThrottle');
    }
}

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
     * @var string database table (prefix) for messages
     */
    const DbTableMessages = 'xf_threemagw_messages';

    /**
     * @var string database table for files
     */
    const DbTableFiles = 'xf_threemagw_files';

    /**
     * Gets the fields that are defined for the table. See parent for explanation.
     *
     * @see XenForo_DataWriter::_getFields()
     * @return array
     */
    protected function _getFields()
    {
        return [
            self::DbTableMessages => [
                'message_id' => [
                    'type' => self::TYPE_STRING,
                    'required'  => true,
                    'maxLength' => 16
                ],
                'message_type_code' => [
                    'type' => self::TYPE_UINT,
                    'required'  => true,
                    'maxLength' => 10
                ],
                'sender_threema_id' => [
                    'type' => self::TYPE_STRING,
                    'required'  => true,
                    'maxLength' => 8
                ],
                'date_send' => [
                    'type' => self::TYPE_UINT,
                    'required'  => true
                ],
                'date_received' => [
                    'type' => self::TYPE_UINT,
                    'required'  => true,
                    'default' => XenForo_Application::$time
                ]
            ],
            self::DbTableFiles => [
                'file_id' => [
                    'type' => self::TYPE_UINT,
                    'maxLength' => 10,
                    'autoIncrement' => true
                ],
                'message_id' => [
                    'type' => self::TYPE_STRING,
                    'required'  => true,
                    'maxLength' => 16
                ],
                'file_path' => [
                    'type' => self::TYPE_STRING,
                    'required'  => true,
                    'maxLength' => 255
                ],
                'file_type' => [
                    'type' => self::TYPE_STRING,
                    'required'  => true,
                    'maxLength' => 100
                ],
                'is_saved' => [
                    'type' => self::TYPE_BOOLEAN,
                    'required'  => true,
                    'maxLength' => 1,
                    'default' => true
                ]
            ],
            self::DbTableMessages . '_delivery_receipt' => [
                'message_id' => [
                    'type' => self::TYPE_STRING,
                    'required'  => true,
                    'maxLength' => 16
                ],
                'receipt_type' => [
                    'type' => self::TYPE_UINT,
                    'required'  => true,
                    'maxLength' => 3
                ]
            ],
            self::DbTableMessages . '_file' => [
                'message_id' => [
                    'type' => self::TYPE_STRING,
                    'required'  => true,
                    'maxLength' => 16
                ],
                'file_size' => [
                    'type' => self::TYPE_UINT,
                    'required'  => true,
                    'maxLength' => 10
                ],
                'file_name' => [
                    'type' => self::TYPE_STRING,
                    'required'  => true,
                    'maxLength' => 255
                ],
                'mime_type' => [
                    'type' => self::TYPE_STRING,
                    'required'  => true,
                    'maxLength' => 255
                ]
            ],
            self::DbTableMessages . '_image' => [
                'message_id' => [
                    'type' => self::TYPE_STRING,
                    'required'  => true,
                    'maxLength' => 16
                ],
                'file_size' => [
                    'type' => self::TYPE_UINT,
                    'required'  => true,
                    'maxLength' => 10
                ]
            ],
            self::DbTableMessages . '_text' => [
                'message_id' => [
                    'type' => self::TYPE_STRING,
                    'required'  => true,
                    'maxLength' => 16
                ],
                'text' => [
                    'type' => self::TYPE_STRING,
                    'required'  => true
                ]
            ]
        ];
    }

    /**
     * Gets the actual existing data out of data that was passed in. See parent for explanation.
     *
     * As an update cannot happen in the message tables anyway, this function is
     * not implemented in any way.
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
     * As an update cannot happen in the message tables anyway, this function is
     * not implemented in any way.
     *
     * @see XenForo_DataWriter::_getUpdateCondition()
     * @return bool
     */
    protected function _getUpdateCondition($tableName)
    {
        return '';
    }

    /**
     * Removes tables, which should not be touched.
     *
     * The function searches for invalid tables and removes them from the query.
     * This is neccessary as a message can only be an instance of one message
     * type and as by default all tables (& therefore types) are included in the
     * fields, we have to confitionally remove them.
     * Additionally it ses the correct character encoding.
     *
     * @see XenForo_DataWriter::_preSave()
     * @return bool
     */
    protected function _preSave()
    {
        // filter data
        $newData = $this->getNewData();
        foreach ($this->getTables() as $tableName) {
            // search for (invalid) tables with
            if (
                !array_key_exists($tableName, $newData) || // no data OR
                !array_key_exists('message_id', $newData[$tableName]) || // missing message_id OR
                count($newData[$tableName]) == 1 // message_id as the only data set
            ) {
                // and remove them
                unset($this->_fields[$tableName]);
            }
        }

        // set correct character encoding
        $this->_db->query('SET NAMES utf8mb4');

        return '';
    }

    /**
     * Get the messages model.
     *
     * @return ThreemaGateway_Model_Messages
     */
    protected function _getMessagesModel()
    {
        return $this->getModelFromCache('ThreemaGateway_Model_Messages');
    }
}

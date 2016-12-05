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
     * @var string extra data - files
     */
    const DataFiles = 'files';

    /**
     * @var string extra data - acknowledged message IDs
     */
    const DataAckedMsgIds = 'ack_message_id';

    /**
     * Gets the fields that are defined for the table. See parent for explanation.
     *
     * @see XenForo_DataWriter::_getFields()
     * @return array
     */
    protected function _getFields()
    {
        return [
            ThreemaGateway_Model_Messages::DbTableMessages => [
                'message_id' => [
                    'type' => self::TYPE_STRING,
                    'required'  => true,
                    'maxLength' => 16
                ],
                'message_type_code' => [
                    'type' => self::TYPE_UINT
                ],
                'sender_threema_id' => [
                    'type' => self::TYPE_STRING,
                    'maxLength' => 8
                ],
                'date_send' => [
                    'type' => self::TYPE_UINT
                ],
                'date_received' => [
                    'type' => self::TYPE_UINT,
                    'required' => true,
                    'default' => XenForo_Application::$time
                ]
            ],
            ThreemaGateway_Model_Messages::DbTableFiles => [
                'file_id' => [
                    'type' => self::TYPE_UINT,
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
            ThreemaGateway_Model_Messages::DbTableAckMsgs => [
                'ack_id' => [
                    'type' => self::TYPE_UINT,
                    'autoIncrement' => true
                ],
                'message_id' => [
                    'type' => self::TYPE_STRING,
                    'required'  => true,
                    'maxLength' => 16
                ],
                'ack_message_id' => [
                    'type' => self::TYPE_STRING,
                    'required'  => true,
                    'maxLength' => 16
                ]
            ],
            ThreemaGateway_Model_Messages::DbTableMessages . '_delivery_receipt' => [
                'message_id' => [
                    'type' => self::TYPE_STRING,
                    'required'  => true,
                    'maxLength' => 16
                ],
                'receipt_type' => [
                    'type' => self::TYPE_UINT,
                    'required'  => true
                ]
            ],
            ThreemaGateway_Model_Messages::DbTableMessages . '_file' => [
                'message_id' => [
                    'type' => self::TYPE_STRING,
                    'required'  => true,
                    'maxLength' => 16
                ],
                'file_size' => [
                    'type' => self::TYPE_UINT,
                    'required'  => true
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
            ThreemaGateway_Model_Messages::DbTableMessages . '_image' => [
                'message_id' => [
                    'type' => self::TYPE_STRING,
                    'required'  => true,
                    'maxLength' => 16
                ],
                'file_size' => [
                    'type' => self::TYPE_UINT,
                    'required'  => true
                ]
            ],
            ThreemaGateway_Model_Messages::DbTableMessages . '_text' => [
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
     * Generalises the receive date to reduce the amount of stored meta data.
     *
     * Generally you may also want to call this if the data you are inserting
     * is only placeholder data (aka the message ID + receive date).
     * All existing data should already be set when calling this function.
     */
    public function roundReceiveDate()
    {
        $this->set('date_received', $this->getRoundedReceiveDate());
    }

    /**
     * Normalizes the file path returned by the PHP SDK to a common format.
     *
     * Currently this removes the directory structure, so that only the file
     * name is saved.
     *
     * @param string $filepath
     * @return string
     */
    public function normalizeFilePath($filepath)
    {
        return basename($filepath);
    }

    /**
     * Gets the actual existing data out of data that was passed in. See parent for explanation.
     *
     * The implementation is incomplete as it only builds an array with message
     * ids and no real data. This is however done on purpose as this function is
     * currently only used for deleting data. Updates can never happen in any
     * message table.
     *
     * @param mixed
     * @see XenForo_DataWriter::_getExistingData()
     * @return array
     */
    protected function _getExistingData($data)
    {
        /** @var string $messageId */
        if (!$messageId = $this->_getExistingPrimaryKey($data, 'message_id')) {
            return false;
        }

        /** @var array $existing Array of existing data. (filled below) */
        $existing = [];

        $this->_getMessagesModel()->setMessageId($messageId);
        /** @var array $metaData */
        $metaData = $this->_getMessagesModel()->getMessageMetaData();

        // add main table to array (this is the only complete table using)
        $existing[ThreemaGateway_Model_Messages::DbTableMessages] = reset($metaData);

        /** @var int $messageType Extracted message type from metadata. */
        $messageType = reset($metaData)['message_type_code'];

        // conditionally add data from other tables depending on message
        // type
        switch ($messageType) {
            case ThreemaGateway_Model_Messages::TypeCode_DeliveryMessage:
                $existing[ThreemaGateway_Model_Messages::DbTableMessages . '_delivery_receipt'] = [
                    'message_id' => $messageId
                ];
                $existing[ThreemaGateway_Model_Messages::DbTableAckMsgs] = [
                    'message_id' => $messageId
                ];
                break;

            case ThreemaGateway_Model_Messages::TypeCode_FileMessage:
                $existing[ThreemaGateway_Model_Messages::DbTableMessages . '_file'] = [
                    'message_id' => $messageId
                ];
                $existing[ThreemaGateway_Model_Messages::DbTableFiles] = [
                    'message_id' => $messageId
                ];
                break;

            case ThreemaGateway_Model_Messages::TypeCode_ImageMessage:
                $existing[ThreemaGateway_Model_Messages::DbTableMessages . '_image'] = [
                    'message_id' => $messageId
                ];
                $existing[ThreemaGateway_Model_Messages::DbTableFiles] = [
                    'message_id' => $messageId
                ];
                break;

            case ThreemaGateway_Model_Messages::TypeCode_TextMessage:
                $existing[ThreemaGateway_Model_Messages::DbTableMessages . '_text'] = [
                    'message_id' => $messageId
                ];
                $existing[ThreemaGateway_Model_Messages::DbTableFiles] = [
                    'message_id' => $messageId
                ];
                break;

            default:
                throw new XenForo_Exception(new XenForo_Phrase('threemagw_unknown_message_type'));
                break;
        }

        return $existing;
    }

    /**
     * Gets SQL condition to update the existing record.
     *
     * @see XenForo_DataWriter::_getUpdateCondition()
     * @return bool
     */
    protected function _getUpdateCondition($tableName)
    {
        return 'message_id = ' . $this->_db->quote($this->getExisting('message_id'));
    }

    /**
     * Pre-save: Removes tables, which should not be touched.
     *
     * The function searches for invalid tables and removes them from the query.
     * This is necessary as a message can only be an instance of one message
     * type and as by default all tables (& therefore types) are included in the
     * fields, we have to confitionally remove them.
     * Additionally it ses the correct character encoding.
     *
     * @see XenForo_DataWriter::_preSave()
     */
    protected function _preSave()
    {
        // filter data
        // also uses existing data as a data base as otherwise the main table
        // may also get deleted because of missing message id
        $newData = array_merge($this->getNewData(), $this->_existingData);

        foreach ($this->getTables() as $tableName) {
            // search for (invalid) tables with
            if (
                !array_key_exists($tableName, $newData) || // no data OR
                !array_key_exists('message_id', $newData[$tableName]) || // missing message_id OR
                (count($newData[$tableName]) == 1 && $tableName != ThreemaGateway_Model_Messages::DbTableMessages) // message_id as the only data set (and it's not the main message table where this is valid)
            ) {
                // and remove them
                unset($this->_fields[$tableName]);
            }
        }

        // check whether there is other data in the main table
        /** @var bool $isData whether in the main table is other data than the message ID */
        $isData = false;
        foreach ($this->_fields[ThreemaGateway_Model_Messages::DbTableMessages] as $field => $fieldData) {
            if ($field == 'message_id') {
                // skip as requirement already checked
                continue;
            }
            if ($field == 'date_received') {
                continue;
            }

            if ($this->getNew($field, ThreemaGateway_Model_Messages::DbTableMessages)) {
                $isData = true;
                break;
            }
        }

        // validate data (either main table contains only basic data *OR* it requires all data fields)
        foreach ($this->_fields[ThreemaGateway_Model_Messages::DbTableMessages] as $field => $fieldData) {
            if ($field == 'message_id') {
                // skip as requirement already checked
                continue;
            }
            if ($field == 'date_received') {
                continue;
            }

            // table contains data
            if ($isData) {
                //but required key is missing
                if (
                    !$this->getNew($field, ThreemaGateway_Model_Messages::DbTableMessages) &&
                    !isset($fieldData['default']) // exception: a default value is set
                ) {
                    $this->_triggerRequiredFieldError(ThreemaGateway_Model_Messages::DbTableMessages, $field);
                }
            } else {
                // table does not contain data,
                // so make sure data is really "null" and not some other type of data by removing it completly from the model
                unset($this->_newData[ThreemaGateway_Model_Messages::DbTableMessages][$field]);
                unset($this->_fields[ThreemaGateway_Model_Messages::DbTableMessages][$field]);
            }
        }

        // set correct character encoding
        $this->_db->query('SET NAMES utf8mb4');
    }

    /**
     * Pre-delete: Remove main table & unused tables from selected existing data.
     *
     * The reason for the deletion is, that the message ID should stay in the
     * database and must not be deleted.
     *
     * @see XenForo_DataWriter::_preDelete()
     */
    protected function _preDelete()
    {
        // we may need to store the message ID to prevent replay attacks
        if (ThreemaGateway_Helper_Message::isAtRiskOfReplayAttack($this->_existingData[ThreemaGateway_Model_Messages::DbTableMessages])) {
            // remove main table from deletion as it is handled in _postDelete().
            unset($this->_fields[ThreemaGateway_Model_Messages::DbTableMessages]);
        }

        // similar to _preSave() filter data
        foreach ($this->getTables() as $tableName) {
            // search for (invalid) tables with
            if (
                !array_key_exists($tableName, $this->_existingData) || // no data OR
                !array_key_exists('message_id', $this->_existingData[$tableName]) // missing message_id
            ) {
                // and remove them
                unset($this->_fields[$tableName]);
            }
        }
    }

    /**
     * Post-save: Add additional data supplied as extra data.
     *
     * This function writes the missing datasets into the files and the
     * acknowleged messages table.
     *
     * @see XenForo_DataWriter::_postSave()
     */
    protected function _postSave()
    {
        // get data
        $allFiles    = $this->getExtraData(self::DataFiles);
        $ackedMsgIds = $this->getExtraData(self::DataAckedMsgIds);

        // add additional data
        if ($allFiles) {
            foreach ($allFiles as $fileType => $filePath) {
                // get table fields
                /** @var array $tableFields fields of table "files" */
                $tableFields = $this->_getFields()[ThreemaGateway_Model_Messages::DbTableFiles];
                // remove keys, which are automatically set
                unset($tableFields['file_id']);  // (auto increment)
                unset($tableFields['is_saved']); // (default value=1)
                // we do only care about the keys
                /** @var array $tableKeys extracted keys from fields */
                $tableKeys = array_keys($tableFields);

                // create insert query for this item
                $this->_db->query('INSERT INTO `' . ThreemaGateway_Model_Messages::DbTableFiles . '`
                    ( `' . implode('`, `',  $tableKeys) . '`)
                    VALUES (' . implode(', ', array_fill(0, count($tableKeys), '?')) . ')', // only (?, ?, ...)
                    [
                        $this->get('message_id'), //message_id
                        $this->normalizeFilePath($filePath), //file_path
                        $fileType, //file_type
                    ]);
            }
        }

        if ($ackedMsgIds) {
            foreach ($ackedMsgIds as $ackedMessageId) {
                // get table fields
                /** @var array $tableFields fields of table "ackmsgs" */
                $tableFields = $this->_getFields()[ThreemaGateway_Model_Messages::DbTableAckMsgs];
                // remove key(s), which are automatically set
                unset($tableFields['ack_id']); // (auto increment)
                // we do only care about the keys
                /** @var array $tableKeys extracted keys from fields */
                $tableKeys = array_keys($tableFields);

                // create insert query for this item
                $this->_db->query('INSERT INTO `' . ThreemaGateway_Model_Messages::DbTableAckMsgs . '`
                    ( `' . implode('`, `',  $tableKeys) . '`)
                    VALUES (' . implode(', ', array_fill(0, count($tableKeys), '?')) . ')', // only (?, ?, ...)
                    [
                        $this->get('message_id'), //message_id
                        $ackedMessageId, //ack_message_id
                    ]);
            }
        }
    }

    /**
     * Post-delete: Remove all data from main table, except of message ID &
     * the receive date.
     *
     * The reason for the deletion is, that the message ID should stay in the
     * database and must not be deleted as this prevents replay attacks
     * ({@see ThreemaGateway_Handler_Action_Receiver->removeMessage()}).
     *
     * @see XenForo_DataWriter::_postDelete()
     */
    protected function _postDelete()
    {
        // skip custom deletion if main table has already been deleted and is
        // therefore stil in the fields array
        if (isset($this->_fields[ThreemaGateway_Model_Messages::DbTableMessages])) {
            return;
        }

        // get table fields
        /** @var array $tableFields fields of main message table */
        $tableFields = $this->_getFields()[ThreemaGateway_Model_Messages::DbTableMessages];
        // remove keys, which should stay in the database
        unset($tableFields['message_id']);
        unset($tableFields['date_received']);
        // date_received is not removed as this is needed for real deletion;
        // below it is also updated to a generalised value to reduce the amount
        // of saved meta data

        // we do only care about the keys
        /** @var array $tableKeys extracted keys from fields */
        $tableKeys = array_keys($tableFields);

        // remove values from database
        $this->_db->query('UPDATE `' . ThreemaGateway_Model_Messages::DbTableMessages . '`
            SET `' . implode('`=null, `',  $tableKeys) . '`=null,
            `date_received`=' . $this->_db->quote($this->getRoundedReceiveDate()) . '
            WHERE ' . $this->getUpdateCondition(ThreemaGateway_Model_Messages::DbTableMessages));
    }

    /**
     * Gets the receive date in a rounded way.
     *
     * @return int
     */
    protected function getRoundedReceiveDate()
    {
        /* @var int|null $receiveDate */
        // get specified value
        $receiveDate = $this->get('date_received');

        // get default if not set
        if (!$receiveDate) {
            $receiveDate = $this->_getFields()[ThreemaGateway_Model_Messages::DbTableMessages]['date_received']['default'];
        }

        // round unix time to day (00:00)
        $receiveDate = floor($receiveDate / 60 / 60 / 24) * 24 * 60 * 60;

        return (int) $receiveDate;
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

<?php
/**
 * Model for messages stored in database.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Model_Messages extends XenForo_Model
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
     * @var string database table for acknowledged messages
     */
    const DbTableAckMsgs = 'xf_threemagw_ackmsgs';

    /**
     * @var int constant for type code
     */
    const TypeCode_DeliveryMessage = 0x80;

    /**
     * @var int constant for type code
     */
    const TypeCode_FileMessage = 0x17;

    /**
     * @var int constant for type code
     */
    const TypeCode_ImageMessage = 0x02;

    /**
     * @var int constant for type code
     */
    const TypeCode_TextMessage = 0x01;

    /**
     * Execute this before any query.
     *
     * Sets internal values neccessary for a correct connection to the database.
     *
     */
    public function preQuery()
    {
        // set correct character encoding
        $this->_getDb()->query('SET NAMES utf8mb4');
    }

    /**
     * Returns the single last message received.
     *
     * @param string $threemaId filter by Threema ID (optional)
     * @param string $messageType filter by message type (optional, use constants)
     * @return null|array
     */
    public function getLastMessage($threemaId = null, $messageType = null)
    {
        // TODO: implement
    }

    /**
     * Returns all messages with the speciffied criterias.
     *
     * @param string $threemaId filter by Threema ID (optional)
     * @param string $messageType
     * @return null|array
     */
    public function getMessages($threemaId = null, $messageType = null, $timeSpan = null)
    {
        // TODO: implement
    }

    /**
     * Returns the message data for a particular message ID.
     *
     * @param string $messageId
     * @throws XenForo_Exception
     * @return null|array
     */
    public function getMessageData($messageId)
    {
        // get basic information
        $metaData = $this->getMessageMetaData($messageId);

        if (!$metaData) {
            return false;
        }

        switch ($metaData['message_type_code']) {
            case self::TypeCode_DeliveryMessage:
                // $result = $this->_getDb()->fetchRow('
                //     SELECT * FROM `' . self::DbTableMessages . '_delivery_receipt`
                //     WHERE `message_id` = ?',
                //     $messageId);
                break;

            case self::TypeCode_FileMessage:
                $result = $this->_getDb()->fetchAll('
                    SELECT filemessage.*, filelist.*
                    FROM `' . self::DbTableMessages . '_file` AS `filemessage`
                    INNER JOIN `' . self::DbTableFiles . '` AS `filelist` ON
                    (filelist.message_id = filemessage.message_id)
                    WHERE (filemessage.message_id = ?) AND (filelist.is_saved)
                    ', $messageId);

                // throw error if data is missing
                if (!is_array($result)) {
                    throw new XenForo_Exception(new XenForo_Phrase('threemagw_missing_database_data'));
                }

                $output = $this->pushArrayKeys($metaData, $result, [
                    'message_id',
                    'file_name',
                    'mime_type',
                    'file_size'
                ]);
                $output['files'] = $result;
                break;

            case self::TypeCode_ImageMessage:
                # code...
                break;

            case self::TypeCode_TextMessage:
                // text messages do not have any additional data associated, so
                // we can do a simple query here
                $result = $this->_getDb()->fetchRow('
                    SELECT * FROM `' . self::DbTableMessages . '_text`
                    WHERE `message_id` = ?',
                    $messageId);
                $output = array_merge($metaData, $result);
                break;

            default:
                throw new XenForo_Exception(new XenForo_Phrase('threemagw_unknown_message_type'));
                break;
        }

        // throw error if data is missing
        if (!is_array($result)) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_missing_database_data'));
        }

        return $output;
    }

    /**
     * Returns the list of all files .
     *
     * @param string $threemaId filter by Threema ID (optional)
     * @param string $mimeType Filter by mime type (optional).
     * @return null|array
     */
    public function getFileList($mimeType = null, $threemaId = null, $messageId = null)
    {
        // TODO: implement
    }

    /**
     * Returns the current state of a particular message.
     *
     * @param string $messageSentId The ID of message, which has been send to a user
     * @return null|array
     */
    public function getMessageState($messageSentId)
    {
        // TODO: implement
    }

    /**
     * Returns the history of all state changes of a particular message.
     *
     * @param string $messageSentId The ID of message, which has been send to a user
     * @return null|array
     */
    public function getMessageStateHistory($messageSentId)
    {
        // TODO: implement
    }

    /**
     * Returns only the meta data of a message not depending on the type
     * of the message.
     *
     * @param string $messageId
     * @return null|array
     */
    public function getMessageMetaData($messageId)
    {
        $result = $this->_getDb()->fetchRow('SELECT * FROM `' . self::DbTableMessages . '`
            WHERE `message_id` = ?',
            $messageId);

        // fail if there is no data
        if (!is_array($result)) {
            return false;
        }

        return $result;
    }

    /**
     * Removes the specified keys from the second array and pushes them into
     * the first base array.
     * The subarray must be indexed by integers, where each index contains an
     * associative array with the keys to remove.
     * It does not validate the data and assumes that the 0-index of $subArray
     * there, including the data, whcih should be pushed to $baseArray.
     *
     * @param array $baseArray the main array, where the key/value pairs get to
     * @param array $subArray the array, which keys should be removed
     * @param array $removeKeys an array of keys, which should be removed
     *
     * @return array
     */
    protected function pushArrayKeys(&$baseArray, &$subArray, $removeKeys)
    {
        foreach ($removeKeys as $key) {
            // move value from subarray to base array
            $baseArray[$key] = $subArray[0][$key];

            // then delete from sub array
            for ($i=0; $i < count($subArray); $i++) {
                unset($subArray[$i][$key]);
            }
        }

        return $baseArray;
    }
}

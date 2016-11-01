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
     * @var array[string] conditions for the data query for meta data
     */
    protected $whereMetaData = [];

    /**
     * @var array[string] the values, whcih should be inserted
     */
    protected $fetchMetaData = [];

    /**
     * Execute this before any query.
     *
     * Sets internal values neccessary for a correct connection to the database.
     */
    public function preQuery()
    {
        // set correct character encoding
        $this->_getDb()->query('SET NAMES utf8mb4');
    }

    /**
     * Sets the message ID for querying it.
     *
     * @param string $messageId
     */
    public function setMessageId($messageId)
    {
        $this->whereMetaData[] = 'message_id = ?';
        $this->fetchMetaData[] = $messageId;
    }

    /**
     * Execute this before any query.
     *
     * @param string $threemaId
     */
    public function setSenderId($threemaId)
    {
        $this->whereMetaData[] = 'sender_threema_id = ?';
        $this->fetchMetaData[] = $threemaId;
    }

    /**
     * Execute this before any query.
     *
     * @param string $threemaId
     */
    public function setTypeCode($typeCode)
    {
        $this->whereMetaData[] = 'message_type_code = ?';
        $this->fetchMetaData[] = $typeCode;
    }

    /**
     * Execute this before any query.
     *
     * @param int $date_min oldest date of messages
     * @param int $date_max latest date of messages (optional)
     */
    public function setTimeLimit($date_min, $date_max = null)
    {
        $this->whereMetaData[] = 'date_send >= ?';
        $this->fetchMetaData[] = $date_min;
        if ($date_max) {
            $this->whereMetaData[] = 'date_send <= ?';
            $this->fetchMetaData[] = $date_max;
        }
    }

    /**
     * Queries all available data from a list of message IDs.
     *
     * @param  array[string]     $messageIds         The message IDs
     * @param  bool              $groupByMessageType Set to true to group the return value via
     *                                               message types. (default: false)
     * @throws XenForo_Exception
     * @return null|array
     */
    public function getFullData($messageIds, $groupByMessageType = false)
    {
        // get grouped messages by type
        $messageTypesIds = $this->groupArray($messageIds, 'message_type_code');
        // we always need to do this (regardless of message_type_code) as each
        // message type needs to be handled individually

        // query message types individually
        $output = [];
        foreach ($messageTypesIds as $messageType => $messages) {
            // get messages of current data type in groups & query results
            $groupedMessages = $this->groupArray($messages, 'message_id', true);
            $groupedResult   = $this->getMessageDataByType($this->getMessageIdsFromResult($messages), $messageType);

            // go through each message to merge result with meta data
            foreach ($groupedMessages as $msgId => $msgMetaData) {
                // ignore non-exisiting keys
                if (!array_key_exists($msgId, $groupedResult)) {
                    continue;
                }

                // merge arrays
                $mergedArrays = $msgMetaData + $groupedResult[$msgId];

                // remove unneccessary message_id (the ID is already the key)
                if (array_key_exists('message_id', $mergedArrays)) {
                    unset($mergedArrays['message_id']);
                }

                // save as output
                if ($groupByMessageType) {
                    // remove unneccessary message_type_code (as it is already
                    // grouped by it)
                    if (array_key_exists('message_id', $mergedArrays)) {
                        unset($mergedArrays['message_id']);
                    }

                    $output[$messageType][$msgId] = $mergedArrays;
                } else {
                    $output[$msgId] = $mergedArrays;
                }
            }
        }

        // reorder messages if neccessary
        if (!$groupByMessageType && count($messageTypesIds) > 1) {
            //TODO: reorder!
        }

        return $output;
    }

    /**
     * Queries all available data for a message type.
     *
     * @param  array[string]     $messageIds  The message IDs
     * @param  int               $messageType The message type the messages belong to
     * @throws XenForo_Exception
     * @return null|array
     */
    public function getMessageDataByType($messageIds, $messageType)
    {
        $whereClause = 'message.message_id IN (' . implode(', ', array_fill(0, count($messageIds), '?')) . ')';

        // query data
        $output      = null;
        $result      = null;
        $resultindex = '';
        switch ($messageType) {
            case self::TypeCode_DeliveryMessage:
                $result = $this->_getDb()->fetchAll('
                    SELECT message.*, ack_messages.*
                    FROM `' . self::DbTableMessages . '_delivery_receipt` AS `message`
                    INNER JOIN `' . self::DbTableAckMsgs . '` AS `ack_messages` ON
                        (message.message_id = ack_messages.message_id)
                    WHERE ' . $whereClause . '
                    ', $messageIds);

                $resultindex = 'ackmsgs';
                break;

            case self::TypeCode_FileMessage:
                $result = $this->_getDb()->fetchAll('
                    SELECT message.*, filelist.*
                    FROM `' . self::DbTableMessages . '_file` AS `message`
                    INNER JOIN `' . self::DbTableFiles . '` AS `filelist` ON
                        (filelist.message_id = message.message_id)
                    WHERE ' . $whereClause . '
                    ', $messageIds);

                $resultindex = 'files';
                break;

            case self::TypeCode_ImageMessage:
                $result = $this->_getDb()->fetchAll('
                    SELECT message.*, filelist.*
                    FROM `' . self::DbTableMessages . '_image` AS `message`
                    INNER JOIN `' . self::DbTableFiles . '` AS `filelist` ON
                        (filelist.message_id = message.message_id)
                    WHERE ' . $whereClause . '
                    ', $messageIds);

                $resultindex = 'files';
                break;

            case self::TypeCode_TextMessage:
                // text messages do not have any additional data associated, so
                // we can do a simple query here
                $result = $this->_getDb()->fetchAll('
                    SELECT * FROM `' . self::DbTableMessages . '_text` AS `message`
                    WHERE ' . $whereClause,
                    $messageIds);
                $output = $result;
                break;

            default:
                throw new XenForo_Exception(new XenForo_Phrase('threemagw_unknown_message_type'));
                break;
        }

        // throw error if data is missing
        if (!is_array($result)) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_missing_database_data'));
        }
        // if there is no result, just return null
        if (empty($result)) {
            return null;
        }

        // group array by message ID
        $result = $this->groupArray($result, 'message_id');

        // push general properties one array up
        if (!$output && $resultindex) {
            foreach ($result as $msgId => $resultForId) {
                $output[$msgId] = $this->pushArrayKeys($output[$msgId], $resultForId, [
                    'message_id',
                    'file_name',
                    'mime_type',
                    'file_size'
                ]);
                $output[$msgId][$resultindex] = $resultForId;
            }
        }

        return $output;
    }

    /**
     * Returns only the meta data of one or more messages not depending on the
     * type of the message.
     *
     * @return null|array
     */
    public function getMessageMetaData()
    {
        $result = $this->_getDb()->fetchAll('SELECT * FROM `' . self::DbTableMessages . '` AS `message`
            WHERE ' . $this->getConditionsForClause($this->whereMetaData),
            $this->fetchMetaData);

        // fail if there is no data
        if (!is_array($result) || !$result) {
            return null;
        }

        return $result;
    }

    /**
     * Queries all available data from a list of message IDs.
     *
     * @param  array[string]     $messages The message result
     * @throws XenForo_Exception
     * @return null|array
     */
    protected function getMessageIdsFromResult($messages)
    {
        // use PHP function if available (>= PHP 5.5.0)
        if (function_exists('array_column')) {
            return array_column($messages, 'message_id');
        }

        // manually extract message_id from array
        $output = [];
        foreach ($messages as $message) {
            $output[] = $message['message_id'];
        }

        return $output;
    }

    /**
     * Removes the specified keys from the second array and pushes them into
     * the first base array.
     * The subarray must be indexed by integers, where each index contains an
     * associative array with the keys to remove.
     * It assumes that the 0-index of $subArray is there, including the data,
     * which should be pushed to $baseArray.
     *
     * @param array $baseArray  the main array, where the key/value pairs get to
     * @param array $subArray   the array, which keys should be removed
     * @param array $removeKeys an array of keys, which should be removed
     *
     * @throws XenForo_Exception
     * @return false|array
     */
    protected function pushArrayKeys(&$baseArray, &$subArray, $removeKeys)
    {
        foreach ($removeKeys as $key) {
            // skip invalid keys
            if (!is_array($baseArray) ||
                !array_key_exists($key, $baseArray) ||
                !array_key_exists($key, $subArray[0])) {
                continue;
            }

            // move value from subarray to base array
            $baseArray[$key] = $subArray[0][$key];

            // then delete it from sub array
            for ($i = 0; $i < count($subArray); $i++) {
                unset($subArray[$i][$key]);
            }
        }

        return $baseArray;
    }

    /**
     * Groups an array by using the value of a specific index in it.
     *
     * @param array      $array       the array, which is sued as the base
     * @param string|int $index       the value of the key, which should be used
     *                                for indexing
     * @param bool       $ignoreIndex Set to true to ignore multiple values in
     *                                $array. If activated only the last key of
     *                                $array will be placed into the group and
     *                                it will be the only key. This is only
     *                                useful if you know for sure that only one
     *                                key is available.
     *
     * @return array
     */
    protected function groupArray($array, $indexKey, $ignoreIndex = false)
    {
        $output = [];
        foreach ($array as $i => $value) {
            if ($ignoreIndex) {
                $output[$value[$indexKey]] = $value;
            } else {
                $output[$value[$indexKey]][] = $value;
            }
        }

        return $output;
    }
}

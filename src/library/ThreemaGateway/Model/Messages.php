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
     * @var array constant for type code
     */
    const OrderChoice = [
        'id' => 'message_id',
        'date_send' => 'date_send',
        'date_received' => 'date_received',
        'delivery_state' => 'message.receipt_type',
    ];

    /**
     * @var array data used when querying
     */
    protected $fetchOptions = [
        'where' => [],
        'params' => []
    ];

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
     * Inject or modify a fetch option manually.
     *
     * Sets internal values neccessary for a correct connection to the database.
     * This should best be avoided, when it is not really neccessary to change
     * the value directly.
     * It can e.g. be used to reset data. WHen you e.g. want to reset the where
     * caluse call it this way: injectFetchOption('where', []).
     *
     * @param string $option The option name to inject
     * @param mixed  $value  The value of the option to set.
     * @param mixed  $append If set to true, the value is not overriden, but
     *                       just appended as an array. (default: false)
     */
    public function injectFetchOption($option, $value, $append = false)
    {
        if ($append) {
            $this->fetchOptions[$option][] = $value;
        } else {
            $this->fetchOptions[$option] = $value;
        }
    }

    /**
     * Rests all fetch options. This is useful to prevent incorrect or
     * unexpected results when using one model for multiple queries (not
     * recommend) or for e.g. resetting the options before calling
     * {@link fetchAll()};.
     */
    public function resetFetchOptions()
    {
        $this->fetchOptions = [];
        // set empty data, which is required to prevent failing
        $this->fetchOptions['where']  = [];
        $this->fetchOptions['params'] = [];
    }

    /**
     * Sets the message ID(s) for the query.
     *
     * @param string|array $messageIds  one (string) or more (array) message IDs
     * @param string       $tablePrefix The table prefix (optional)
     */
    public function setMessageId($messageIds, $tablePrefix = null)
    {
        return $this->appendMixedCondition(
            ($tablePrefix ? $tablePrefix . '.' : '') . 'message_id',
            $messageIds
        );
    }

    /**
     * Sets the sender Threema ID(s) for querying it/them.
     *
     * @param string $threemaIds one (string) or more (array) Threema IDs
     */
    public function setSenderId($threemaIds)
    {
        return $this->appendMixedCondition(
            'metamessage.sender_threema_id',
            $threemaIds
        );
    }

    /**
     * Sets the type code(s) for querying only one (or a few) type.
     *
     * Please use the TypeCode_* constants for specifying the type code(s).
     * You should avoid using this and rather use {@link getMessageDataByType()}
     * directly if you know the type code.
     * If you want to limit the types you want to query this method would be a
     * good way for you to use.
     *
     * @param string $typeCode one (string) or more (array) mtype codes
     */
    public function setTypeCode($typeCodes)
    {
        return $this->appendMixedCondition(
            'metamessage.message_type_code',
            $typeCodes
        );
    }

    /**
     * Sets a string to look for when querying text messages.
     *
     * The string is processed by MySQL via the `LIKE` command and may
     * therefore contain some wildcards: % for none or any character and
     * _ for exactly one character.
     * Attention: This is only possible when using the text message type!
     * Otherwise your query will fail.
     *
     * @param string $keyword a keyword to look for
     */
    public function setKeyword($keyword)
    {
        $this->fetchOptions['where'][]  = 'message.text LIKE ?';
        $this->fetchOptions['params'][] = $keyword;
    }

    /**
     * Sets the type code for querying only one type.
     *
     * @param int $date_min oldest date of messages
     * @param int $date_max latest date of messages (optional)
     */
    public function setTimeLimit($date_min, $date_max = null)
    {
        $this->fetchOptions['where'][]  = 'metamessage.date_send >= ?';
        $this->fetchOptions['params'][] = $date_min;
        if ($date_max) {
            $this->fetchOptions['where'][]  = 'metamessage.date_send <= ?';
            $this->fetchOptions['params'][] = $date_max;
        }
    }

    /**
     * Limit the result to a number of datasets.
     *
     * @param int $limit    oldest date of messages
     * @param int $date_max latest date of messages (optional)
     */
    public function setResultLimit($limit)
    {
        $this->fetchOptions['limit'] = $limit;
    }

    /**
     * Sets an order for the query.
     *
     * This function overwrites previous values if they were set as ordering by
     * multiple columns is not possible.
     *
     * @param int    $column    the column to order by (see {@link OrderChoice} for valid values)
     * @param string $direction asc or desc
     */
    public function setOrder($column, $direction = 'asc')
    {
        $this->fetchOptions['order']     = $column;
        $this->fetchOptions['direction'] = $direction;
    }

    /**
     * Queries all available data from a list of message IDs.
     *
     * Note that this requires one to have the meta data of the messages already
     * and therefore you have to run {@link getMessageMetaData()} before and
     * submit it as the first parameter.
     * This method also resets the conditions of the where clause
     * ($fetchOptions['where']) and the params ($fetchOptions['params']) based
     * on the results included in the meta data. Other fetch options however
     * remain and are still applied, so if you want to avoid this, use
     * {@link resetFetchOptions()}.
     * Note that the ordering values of different message types will not work as
     * this function internally needs to handle each message type differently.
     *
     * @param  array[string]     $metaData           The message meta data from
     *                                               {@link getMessageMetaData()}
     *                                               (without grouping)
     * @param  bool              $groupByMessageType Set to true to group the
     *                                               return value via message
     *                                               types. (default: false)
     * @throws XenForo_Exception
     * @return null|array
     */
    public function getAllMessageData($metaData, $groupByMessageType = false)
    {
        // get grouped messages by type
        $messageTypes = $this->groupArray($metaData, 'message_type_code');
        // we always need to do this (regardless of message_type_code) as each
        // message type needs to be handled individually

        // query message types individually
        $output = null;
        foreach ($messageTypes as $messageType => $messages) {
            // get messages of current data type in groups
            $groupedMessages = $this->groupArray($messages, 'message_id', true);

            // overwrite conditions with message IDs we already know
            $this->fetchOptions['params'] = [];
            $this->fetchOptions['where']  = [];
            $this->setMessageId($this->getMessageIdsFromResult($messages), 'message');

            // query data
            $groupedResult = $this->getMessageDataByType($messageType, false);
            // skip processing if there are no results (most likely all
            // messages of this type have been deleted)
            if (!is_array($groupedResult)) {
                continue;
            }

            // go through each message to merge result with meta data
            foreach ($groupedMessages as $msgId => $msgMetaData) {
                // ignore non-exisiting key (might be deleted messages)
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
                    if (array_key_exists('message_type_code', $mergedArrays)) {
                        unset($mergedArrays['message_type_code']);
                    }

                    $output[$messageType][$msgId] = $mergedArrays;
                } else {
                    $output[$msgId] = $mergedArrays;
                }
            }
        }

        return $output;
    }

    /**
     * Queries all available data for a message type.
     *
     * The return value should be an array in the same format as the one
     * returned by {@link getAllMessageData()} when $groupByMessageType is set
     * to false. Of course, however, only one message type is returned here.
     *
     * @param  int               $messageType     The message type the messages belong to
     * @param  bool              $includeMetaData Set to true to also include the main
     *                                            message table in your query. If you do so you
     *                                            will also get the meta data of the message.
     *                                            (default: true)
     * @throws XenForo_Exception
     * @return null|array
     */
    public function getMessageDataByType($messageType, $includeMetaData = true)
    {
        // add table if neccessary
        $extraSelect = '';
        $extraJoin   = '';
        if ($includeMetaData) {
            $extraSelect = ', metamessage.*';
            $extraJoin   = 'INNER JOIN `' . self::DbTableMessages . '` AS `metamessage` ON
                (message.message_id = metamessage.message_id)';
        }

        // prepare query
        /** @var string $limitOptions */
        $limitOptions     = $this->prepareLimitFetchOptions($this->fetchOptions);
        /** @var string $conditionsClause */
        $conditionsClause = $this->getConditionsForClause($this->fetchOptions['where']);
        /** @var string $orderByClause */
        $orderByClause    = $this->getOrderByClause(self::OrderChoice, $this->fetchOptions);

        // query data
        /** @var array|null $output */
        $output      = null;
        /** @var array|null $result database query result */
        $result      = null;
        /** @var string $resultindex index to use for additional data from query */
        $resultindex = '';
        switch ($messageType) {
            case self::TypeCode_DeliveryMessage:
                $result = $this->_getDb()->fetchAll(
                    $this->limitQueryResults('
                        SELECT message.*, ack_messages.* ' . $extraSelect . '
                        FROM `' . self::DbTableMessages . '_delivery_receipt` AS `message`
                        ' . $extraJoin . '
                        INNER JOIN `' . self::DbTableAckMsgs . '` AS `ack_messages` ON
                            (message.message_id = ack_messages.message_id)
                        WHERE ' . $conditionsClause . '
                        ' . $orderByClause . '
                    ', $limitOptions['limit'], $limitOptions['offset']),
                $this->fetchOptions['params']);

                $resultindex = 'ackmsgs';
                break;

            case self::TypeCode_FileMessage:
                $result = $this->_getDb()->fetchAll(
                    $this->limitQueryResults('
                        SELECT message.*, filelist.* ' . $extraSelect . '
                        FROM `' . self::DbTableMessages . '_file` AS `message`
                        ' . $extraJoin . '
                        INNER JOIN `' . self::DbTableFiles . '` AS `filelist` ON
                            (filelist.message_id = message.message_id)
                        WHERE ' . $conditionsClause . '
                        ' . $orderByClause . '
                    ', $limitOptions['limit'], $limitOptions['offset']),
                $this->fetchOptions['params']);

                $resultindex = 'files';
                break;

            case self::TypeCode_ImageMessage:
                $result = $this->_getDb()->fetchAll(
                    $this->limitQueryResults('
                        SELECT message.*, filelist.* ' . $extraSelect . '
                        FROM `' . self::DbTableMessages . '_image` AS `message`
                        ' . $extraJoin . '
                        INNER JOIN `' . self::DbTableFiles . '` AS `filelist` ON
                            (filelist.message_id = message.message_id)
                        WHERE ' . $conditionsClause . '
                        ' . $orderByClause . '
                    ', $limitOptions['limit'], $limitOptions['offset']),
                $this->fetchOptions['params']);

                $resultindex = 'files';
                break;

            case self::TypeCode_TextMessage:
                $result = $this->_getDb()->fetchAll(
                    $this->limitQueryResults('
                        SELECT message.* ' . $extraSelect . '
                        FROM `' . self::DbTableMessages . '_text` AS `message`
                        ' . $extraJoin . '
                        WHERE ' . $conditionsClause . '
                        ' . $orderByClause . '
                    ', $limitOptions['limit'], $limitOptions['offset']),
                $this->fetchOptions['params']);

                // although this is not strictly neccessary for the ease of
                // processing the data later, we also index this
                $resultindex = 'text';
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

        // attributes to remove/push
        $removeAttributes = [
            'message_id',
            'file_name',
            'mime_type',
            'file_size'
        ];
        if ($includeMetaData) {
            $removeAttributes = array_merge($removeAttributes, [
                'message_type_code',
                'sender_threema_id',
                'date_send',
                'date_received'
            ]);
        }

        // push general attributes one array up
        if (!$resultindex) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_unknown_message_type'));
            break;
        }

        // go through each message
        foreach ($result as $msgId => $resultForId) {
            // $output = [];
            $output[$msgId] = $this->pushArrayKeys($output[$msgId],
                                    $resultForId,
                                    $removeAttributes);
            $output[$msgId][$resultindex] = $resultForId;

            // remove unneccessary message_id (the ID is already the key)
            if (array_key_exists('message_id', $output[$msgId])) {
                unset($output[$msgId]['message_id']);
            }
        }

        return $output;
    }

    /**
     * Returns only the meta data of one or more messages not depending on the
     * type of the message.
     *
     * @param  bool       $groupById     Set to true to group the data by the message ID
     * @param  bool       $ignoreInvalid Set to true to remove data sets where the message content may be deleted
     * @return null|array
     */
    public function getMessageMetaData($groupById = false, $ignoreInvalid = true)
    {
        $limitOptions = $this->prepareLimitFetchOptions($this->fetchOptions);

        $result = $this->_getDb()->fetchAll(
        $this->limitQueryResults('SELECT * FROM `' . self::DbTableMessages . '` AS `metamessage`
            WHERE ' . $this->getConditionsForClause($this->fetchOptions['where']) . '
            ' . $this->getOrderByClause(self::OrderChoice, $this->fetchOptions),
        $limitOptions['limit'], $limitOptions['offset']),
        $this->fetchOptions['params']);

        // fail if there is no data
        if (!is_array($result) || !$result) {
            return null;
        }

        // remove invalid data sets (where message might be deleted)
        if ($ignoreInvalid) {
            foreach ($result as $i => $msgData) {
                if (!array_key_exists('message_type_code', $msgData) ||
                    !$msgData['message_type_code']) {
                    unset($result[$i]);
                }
            }
        }

        // group array by message ID if wanted
        if ($groupById) {
            $result = $this->groupArray($result, 'message_id');
        }

        return $result;
    }

    /**
     * Returns all available data from a list of message IDs.
     *
     * @param  array[string]     $messages The message result
     * @throws XenForo_Exception
     * @return null|array
     */
    protected function getMessageIdsFromResult(array $messages)
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
    protected function pushArrayKeys(array &$baseArray, array &$subArray, array $removeKeys)
    {
        foreach ($removeKeys as $key) {
            // skip invalid keys
            if (!array_key_exists($key, $subArray[0])) {
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
     * @param string|int $indexKey    the value of the key, which should be used
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
    public function groupArray(array $array, $indexKey, $ignoreIndex = false)
    {
        /** @var array $output */
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

    /**
     * Appends a WHERE condition for either a string or an array.
     *
     * It automatically chooses between a simple `this = ?` or a more complex
     * `this IN (?, ?, ...)`.
     *
     * @param string       $attName  the name of the required attribut
     * @param string|array $attValue the value, which should be required
     *
     * @return array
     */
    protected function appendMixedCondition($attName, $attValue)
    {
        // convert arrays with only one value
        if (is_array($attValue) && count($attValue) == 1) {
            $attValue = $attValue[0];
        }

        if (!is_array($attValue)) {
            $this->fetchOptions['where'][]  = $attName . ' = ?';
            $this->fetchOptions['params'][] = $attValue;
        } else {
            $this->fetchOptions['where'][]  = $attName . ' IN (' . implode(', ', array_fill(0, count($attValue), '?')) . ')';
            $this->fetchOptions['params'] += $attValue;
        }
    }
}

<?php
/**
 * Allows one to query received Threema messages and to delete them.
 *
 * This class is basically a wrapper around the message model used for
 * querying the messages and tries to make it easier to access them.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Handler_Action_Receiver extends ThreemaGateway_Handler_Action_Abstract
{
    /**
     * @var bool whether the model is already prepared
     */
    protected $isPrepared = false;

    /**
     * @var bool whether the permissions are already checked
     */
    protected $isPermissionChecked = false;

    /**
     * @var bool whether the result should be grouped by the message type
     */
    protected $groupByMessageType = false;

    /**
     * Startup.
     *
     * @param bool $alreadyPrepared     If the Message Model is already prepared you
     *                                  may set this to true.
     * @param bool $skipPermissionCheck Set to true to skip the permission check.
     *                                  (not recommend)
     */
    public function __construct($alreadyPrepared = false, $skipPermissionCheck = false)
    {
        parent::__construct();
        $this->isPrepared          = $alreadyPrepared;
        $this->isPermissionChecked = $skipPermissionCheck;
    }

    /**
     * Sets whether the result should be grouped by the message type.
     *
     * The option is ignored when you specify the message type in a function
     * as grouping in this case would not make any sense.
     *
     * @param  bool       $groupByMessageType
     * @return null|array
     */
    public function groupByMessageType($groupByMessageType)
    {
        $this->groupByMessageType = $groupByMessageType;
    }

    /**
     * Returns the single last message received.
     *
     * @param  string     $threemaId   filter by Threema ID (optional)
     * @param  string     $messageType filter by message type (optional, use Model constants)
     * @param  string     $keyword     filter by this string, which represents
     *                                 the text in a text message (Wildcards: * and ?)
     * @return null|array
     */
    public function getLastMessage($threemaId = null, $messageType = null, $keyword = null)
    {
        $this->initiate();
        /** @var ThreemaGateway_Model_Messages $model */
        $model = XenForo_Model::create('ThreemaGateway_Model_Messages');

        // set options
        if ($threemaId) {
            $model->setSenderId($threemaId);
        }
        if ($messageType) {
            $model->setTypeCode($messageType);
        }
        if ($keyword) {
            $keyword = $this->replaceWildcards($keyword);
            $model->setKeyword($keyword);
        }

        // only show last result
        $model->setResultLimit(1);
        // to make sure the message is really the last one, sort it by the send time
        $model->setOrder('date_send', 'desc');

        return $this->execute($model, $messageType);
    }

    /**
     * Returns all messages with the specified criterias.
     *
     * @param  string     $threemaId   filter by Threema ID (optional)
     * @param  string     $messageType filter by message type (optional, use Model constants)
     * @param  string     $keyword     filter by this string, which represents
     *                                 the text in a text message (Wildcards: * and ?)
     * @return null|array
     */
    public function getMessages($threemaId = null, $messageType = null, $timeSpan = null, $keyword = null)
    {
        $this->initiate();
        /** @var ThreemaGateway_Model_Messages $model */
        $model = XenForo_Model::create('ThreemaGateway_Model_Messages');

        // set options
        if ($threemaId) {
            $model->setSenderId($threemaId);
        }
        if ($messageType) {
            $model->setTypeCode($messageType);
        }
        if ($timeSpan) {
            $model->setTimeLimit(strtotime($timeSpan, XenForo_Application::$time));
            // manual ordering is not neccessary as only new messages are inserted
            // ("at the bottom") and the dates never change,
        }
        if ($keyword) {
            $keyword = $this->replaceWildcards($keyword);
            $model->setKeyword($keyword);
        }

        return $this->execute($model, $messageType);
    }

    /**
     * Returns the message data for a particular message ID.
     *
     * Note that when the database is corrupt and e.g. for a message some
     * datasets are missing, thsi will return null.
     *
     * @param  string     $messageId
     * @param  string     $messageType If you know the message type it is very much
     *                                 recommend to specify it here.
     * @return null|array
     */
    public function getMessageData($messageId, $messageType = null)
    {
        $this->initiate();
        /** @var ThreemaGateway_Model_Messages $model */
        $model = XenForo_Model::create('ThreemaGateway_Model_Messages');

        // set options
        if ($messageId) {
            $model->setMessageId($messageId, 'metamessage');
        }
        if ($messageType) {
            $model->setTypeCode($messageType);
        }

        return $this->execute($model, $messageType);
    }

    /**
     * Checks whether a message is saved in the database.
     *
     * Note that this does not guarantee that other methods return any data as
     * a message is also considered "received" when the actual data has
     * already been deleted.
     * All other methods return "null" in this case as they cannot return all of
     * the requested data. This function however would return true.
     *
     * @param  string $messageId
     * @return bool
     */
    public function messageIsReceived($messageId)
    {
        $this->initiate();
        /** @var ThreemaGateway_Model_Messages $model */
        $model = XenForo_Model::create('ThreemaGateway_Model_Messages');

        // validate parameter
        if (!$messageId) {
            throw new XenForo_Exception('Parameter $messageId missing.');
        }

        // set options
        $model->setMessageId($messageId, 'metamessage');

        // query meta data
        if ($model->getMessageMetaData(false, false)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the list of all files.
     *
     * Grouping this result ({@see groupByMessageType()}) is not supported.
     * Note that the result is still the usual array of all other message
     * queries here.
     * Note: When passing a message ID please avoid using other parameters as
     * it may produce errors. When you have a message ID it is also not
     * really neccessary to specify other conditions.
     *
     * @param  string     $threemaId     filter by Threema ID (optional)
     * @param  string     $mimeType      Filter by mime type (optional).
     * @param  string     $fileType      The file type, e.g. thumbnail/file or image (optional).
     *                                   This is a Threema-internal type and may not
     *                                   be particular useful.
     * @param  string     $messageId     If you know the message ID you can skip
     *                                   the previous paeameters and just use this
     *                                   one to get all data.
     * @param  bool       $queryMetaData Set to false, to prevent querying for meta
     *                                   data, which might speed up the query. (default: true)
     * @return null|array
     */
    public function getFileList($mimeType = null, $fileType = null, $threemaId = null, $messageId = null, $queryMetaData = true)
    {
        $this->initiate();
        $model = XenForo_Model::create('ThreemaGateway_Model_Messages');

        // determinate, which message types may be affected
        /** @var string|null $messageType */
        $messageType = null;
        if ($mimeType !== null &&
            $mimeType !== 'image/jpeg') {
            // we can skip the image table as it is impossible that image files
            // would be returned in this query
            $messageType = ThreemaGateway_Model_Messages::TypeCode_FileMessage;

            // and we can already set the mime type as a condition
            $model->injectFetchOption('where', 'message.mime_type = ?', true);
            $model->injectFetchOption('params', $mimeType, true);
        }

        // set options
        if ($messageType) {
            $model->setTypeCode($messageType);
        }
        if ($threemaId) {
            $model->setSenderId($threemaId);
        }

        if ($fileType) {
            $model->injectFetchOption('where', 'filelist.file_type = ?', true);
            $model->injectFetchOption('params', $fileType, true);
        }

        // reset grouping as it cannot be processed
        $this->groupByMessageType = false;

        /** @var array $result */
        $result = null;

        // a message id overtrumps them all :)
        if ($messageId) {
            $model->setMessageId($messageId, 'metamessage');
            // direct query
            $result = $this->execute($model, $messageType);
        } else {
            if ($messageType) {
                // this can only be done when the mime type is set to something
                // different than image/jpeg, as now all images are already
                // excluded
                $result = $model->getMessageDataByType($messageType, true);
            } else {
                // It's more complex if we want to query image & file messages
                // together, as the mime type includes image files.
                //
                // Forunately this problem can be solved by just querying each
                // message type individually. This does also only do 2 queries,
                // which is even less than if we would use getAllMessageData, as
                // there we need 3 queries: metadata + msg type 1 + msg type 2.
                // As we know the possible message types this is possible. In
                // all other ways one must query the metadata and filter or split
                // it accordingly, to later execute the queries.

                // first we query the image files
                // (without mime type setting as images can only have one
                // message type anyway)
                /** @var array|null $images */
                $images = $model->getMessageDataByType(ThreemaGateway_Model_Messages::TypeCode_ImageMessage, true);

                // now set the MIME type if there is one
                if ($mimeType) {
                    $model->injectFetchOption('where', 'message.mime_type = ?', true);
                    $model->injectFetchOption('params', $mimeType, true);
                }

                // and now query all other files
                /** @var array|null $files */
                $files = $model->getMessageDataByType(ThreemaGateway_Model_Messages::TypeCode_FileMessage, true);
                $model->resetFetchOptions();

                // handle empty queries transparently
                if (!$images) {
                    $images = [];
                }
                if (!$files) {
                    $files = [];
                }
                // and combine results
                $result = array_merge($images, $files);

                if (empty($result)) {
                    $result = null;
                }
            }
        }

        return $result;
    }

    /**
     * Returns the current state of a particular message.
     *
     * Only the state of *send* messages can be queried.
     * Note: In contrast to most other methods here, this already returns the
     * message/delivery state as an integer.
     *
     * @param  string   $messageSentId The ID of message, which has been send to a user
     * @return null|int
     */
    public function getMessageState($messageSentId)
    {
        $this->initiate();

        // reset grouping as it cannot be processed
        $this->groupByMessageType = false;

        /** @var array $result */
        $result = $this->getMessageStateHistory($messageSentId, false, 1);
        if (!$result) {
            return null;
        }

        // dig into array
        $result = reset($result)['ackmsgs'];

        // as theoretically one delivery message could include multiple
        // delivery receipts we formally have to walk through the result
        /** @var int $deliveryReceipt */
        $deliveryReceipt = 0;
        foreach ($result as $i => $content) {
            if ($content['receipt_type'] > $deliveryReceipt) {
                $deliveryReceipt = $content['receipt_type'];
            }
        }

        // finally return the state integer
        return $deliveryReceipt;
    }

    /**
     * Returns the history of all state changes of a particular message.
     *
     * Only the state of *send* messages can be queried.
     * The result is already ordered from the not so important state to the most
     * important one.
     *
     * @param  string     $messageSentId The ID of message, which has been send to a user
     * @param  bool       $getMetaData   Set to false, to speed up the query by not
     *                                   asking for meta data (when the state was received etc).
     *                                   (default: false)
     * @param  int        $limitQuery    When set, only the last x states are returned.
     * @return null|array
     */
    public function getMessageStateHistory($messageSentId, $getMetaData = true, $limitQuery = null)
    {
        $this->initiate();
        /** @var ThreemaGateway_Model_Messages $model */
        $model = XenForo_Model::create('ThreemaGateway_Model_Messages');

        $model->injectFetchOption('where', 'ack_messages.ack_message_id = ?', true);
        $model->injectFetchOption('params', $messageSentId, true);

        $model->setOrder('delivery_state', 'desc');

        if ($limitQuery) {
            $model->setResultLimit($limitQuery);
        }

        return $model->getMessageDataByType(ThreemaGateway_Model_Messages::TypeCode_DeliveryMessage, $getMetaData);
    }

    /**
     * Returns an array with all type codes currently supported.
     *
     * @return array
     */
    public function getTypeCodeArray()
    {
        return [
            ThreemaGateway_Model_Messages::TypeCode_DeliveryMessage,
            ThreemaGateway_Model_Messages::TypeCode_FileMessage,
            ThreemaGateway_Model_Messages::TypeCode_ImageMessage,
            ThreemaGateway_Model_Messages::TypeCode_TextMessage
        ];
    }

    /**
     * Remove a message from the database.
     *
     * Note that the message is never completly removed and the message ID will
     * stay in the database.
     * This prevents replay attacks as otherwise a message with the same message
     * ID could be inserted again into the database and would therefore be
     * considered a new message, which has just been received altghough it
     * actually had been received two times.
     * However the message ID alone does not reveal any data (as all data &
     * meta data, even including the message type is deleted).
     *
     * @param string $messageId
     */
    public function removeMessage($messageId)
    {
        $this->initiate();

        /** @var ThreemaGateway_DataWriter_Messages $dataWriter */
        $dataWriter = XenForo_DataWriter::create('ThreemaGateway_DataWriter_Messages');
        $dataWriter->setExistingData($messageId);
        $dataWriter->delete();
    }

    /**
     * Checks whether the user is allowed to receive messages and prepares Model
     * if neccessary.
     *
     * @throws XenForo_Exception
     */
    protected function initiate()
    {
        // check permission
        if (!$this->isPermissionChecked) {
            if (!$this->permissions->hasPermission('receive')) {
                throw new XenForo_Exception(new XenForo_Phrase('threemagw_permission_error'));
            }
            $this->isPermissionChecked = true;
        }

        if (!$this->isPrepared) {
            /** @var ThreemaGateway_Model_Messages $model */
            $model = XenForo_Model::create('ThreemaGateway_Model_Messages');
            $model->preQuery();
            $this->isPrepared = true;
        }
    }

    /**
     * Queries the meta data and the main data of the messages itself.
     *
     * @param ThreemaGateway_Model_Messages $model
     * @param int                           $messageType The type of the message (optional)
     */
    protected function execute($model, $messageType = null)
    {
        if ($messageType) {
            return $model->getMessageDataByType($messageType, true);
        } else {
            // query meta data
            $metaData = $model->getMessageMetaData();
            if (!$metaData) {
                return null;
            }

            $model->resetFetchOptions();

            // query details
            return $model->getAllMessageData($metaData, $this->groupByMessageType);
        }
    }

    /**
     * Replace usual wildcards (?, *) with the ones used by MySQL (%, _).
     *
     * @param  string $string The string to replace
     * @return string
     */
    protected function replaceWildcards($string)
    {
        return str_replace([
            '%',
            '_',
            '*',
            '?',
        ], [
            '\%',
            '\_',
            '%',
            '?',
        ], $string);
    }
}

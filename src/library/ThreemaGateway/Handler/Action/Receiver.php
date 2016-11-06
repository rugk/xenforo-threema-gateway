<?php
/**
 * Allows one to query received Threema messages and to delete them.
 *
 * This class is basically a wrapper around the different Models used for
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
     * @var bool whether the result should be grouped by the message type
     */
    protected $groupByMessageType = false;

    /**
     * Sets whether the result should be grouped by the message type.
     *
     * The option is ignored when you specify the message type in a function
     * as grouping in this case would not make any sense.
     *
     * @param  bool     $groupByMessageType
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
        $model->setOrder('date_send', 'asc');

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
     * @param  string $messageId
     * @param  string $messageType If you know the message type it is very much
     *                             recommend to specify it here.
     * @return null|array
     */
    public function getMessageData($messageId, $messageType = null)
    {
        $this->initiate();
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
     * Returns the list of all files .
     *
     * @param  string     $threemaId filter by Threema ID (optional)
     * @param  string     $mimeType  Filter by mime type (optional).
     * @param  string     $messageId If you know the message ID you can skip
     *                               the previous steps and just use this one
     *                               to get all data.
     * @return null|array
     */
    public function getFileList($mimeType = null, $threemaId = null, $messageId = null)
    {
        // TODO: implement
    }

    /**
     * Returns the current state of a particular message.
     *
     * @param  string     $messageSentId The ID of message, which has been send to a user
     * @return null|array
     */
    public function getMessageState($messageSentId)
    {
        // TODO: implement
    }

    /**
     * Returns the history of all state changes of a particular message.
     *
     * @param  string     $messageSentId The ID of message, which has been send to a user
     * @return null|array
     */
    public function getMessageStateHistory($messageSentId)
    {
        // TODO: implement
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
     * Checks whether the user is allowed to receive messages and prepares Model
     * if neccessary.
     *
     * @throws XenForo_Exception
     */
    protected function initiate()
    {
        // check permission
        if (!$this->permissions->hasPermission('receive')) {
            // throw new XenForo_Exception(new XenForo_Phrase('threemagw_permission_error'));
        }

        if (!$this->isPrepared) {
            $model = XenForo_Model::create('ThreemaGateway_Model_Messages');
            $model->preQuery();
            $this->isPrepared = true;
        }
    }

    /**
     * Queries the meta data and the main data of the messages itself.
     *
     * @param ThreemaGateway_Model_Messages $model
     * @param int $messageType The type of the message (optional)
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
     * @param string $string The string to replace
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

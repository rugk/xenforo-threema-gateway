<?php
/**
 * Allows one to query received Threema messages and to delete them.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Handler_Action_Receiver extends ThreemaGateway_Handler_Action_Abstract
{
    /**
     * @var ThreemaGateway_Model_Messages Variable storing the message model.
     */
    protected $msgModel;

    /**
     * Initiate the receiver.
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->msgModel = new ThreemaGateway_Model_Messages;
    }

    /**
     * Checks whether the user is allowed to receive messages.
     *
     * Usually called only internaly, but in case you manually query the Model
     * we also provide
     *
     * @throws XenForo_Exception
     */
    protected function checkPermission()
    {
        // check permission
        if (!$this->permissions->hasPermission('receive')) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_permission_error'));
        }
    }

    /**
     * Returns the single last message received.
     *
     * @param  string     $threemaId   filter by Threema ID (optional)
     * @param  string     $messageType filter by message type (optional, use Model constants)
     * @return null|array
     */
    public function getLastMessage($threemaId = null, $messageType = null)
    {
        // TODO: implement
    }

    /**
     * Returns all messages with the specified criterias.
     *
     * @param  string     $threemaId   filter by Threema ID (optional)
     * @param  string     $messageType filter by message type (optional, use Model constants)
     * @return null|array
     */
    public function getMessages($threemaId = null, $messageType = null, $timeSpan = null)
    {
        // TODO: implement
    }

    /**
     * Returns the message data for a particular message ID.
     *
     * @param  string            $messageId
     * @throws XenForo_Exception
     * @return null|array
     */
    public function getMessageData($messageId)
    {
        // TODO: implement
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
}

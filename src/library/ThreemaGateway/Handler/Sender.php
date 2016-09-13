<?php
/**
 * Responsible for sending messages in different ways.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Handler_Sender
{
    /**
     * @var ThreemaGateway_Handler $mainHandler
     */
    protected $mainHandler;

    /**
     * Startup.
     *
     */
    public function __construct()
    {
        $this->mainHandler = ThreemaGateway_Handler::getInstance();
    }

    /**
     * Send a message without end-to-end encryption.
     *
     * @param  string            $threemaId
     * @param  string            $message
     *
     * @throws XenForo_Exception
     * @return int               The message ID
     */
    public function sendSimple($threemaId, $message)
    {
        // check permission
        if (!$this->mainHandler->hasPermission('send')) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_permission_error'));
        }

        $threemaId = strtoupper($threemaId);

        /** @var Threema\MsgApi\Receiver $receiver */
        $receiver = new Receiver($threemaId, Receiver::TYPE_ID);

        /** @var Threema\MsgApi\Commands\Results\SendSimpleResult $result */
        $result = $this->mainHandler->getConnector()->sendSimple($receiver, $message);

        if ($result->isSuccess()) {
            return $result->getMessageId();
        } else {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_sending_failed') . ' ' . $result->getErrorMessage());
        }
    }

    /**
     * Send a message to a Threema ID.
     *
     * @param string $threemaId The id where the message should be send to
     * @param string $message   The message to send (max 3500 characters)
     *
     * @throws XenForo_Exception
     * @return int
     */
    public function sendE2EText($threemaId, $message)
    {
        // check permission
        if (!$this->mainHandler->hasPermission('send')) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_permission_error'));
        }

        $threemaId = strtoupper($threemaId);

        $e2eHelper = new E2EHelper(
            ThreemaGateway_Handler_Key::hexToBin($this->PrivateKey),
            $this->mainHandler->getConnector()
        );
        try {
            /** @var Threema\MsgApi\Commands\Results\SendE2EResult $result */
            $result = $e2eHelper->sendTextMessage($threemaId, $message);
        } catch (Exception $e) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_sending_failed') . ' ' . $e->getMessage());
        }

        if ($result->isSuccess()) {
            return $result->getMessageId();
        } else {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_sending_failed') . ' ' . $result->getErrorMessage());
        }
    }


    /**
     * Get status of a message, which has previously been sent.
     *
     * @param  string            $messageId
     *
     * @throws XenForo_Exception
     * @return ???
     */
    public function getStatus($messageId)
    {
        // TODO
    }
}

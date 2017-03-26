<?php
/**
 * Responsible for sending messages in different ways.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Handler_Action_Sender extends ThreemaGateway_Handler_Action_Abstract
{
    /**
     * @var bool whether the permissions are already checked
     */
    protected $isPermissionChecked = false;

    /**
     * Send a message without end-to-end encryption.
     *
     * @param string $threemaId
     * @param string $message
     *
     * @throws XenForo_Exception
     * @return int               The message ID
     */
    public function sendSimple($threemaId, $message)
    {
        $this->initiate();

        /** @var Threema\MsgApi\Receiver $receiver */
        $receiver = $this->getThreemaReceiver($threemaId);

        /** @var Threema\MsgApi\Commands\Results\SendSimpleResult $result */
        $result = $this->getConnector()->sendSimple($receiver, $message);

        return $this->evaluateResult($result);
    }

    /**
     * Send a message to a Threema ID.
     *
     * @param string $threemaId The id where the message should be send to
     * @param string $message   The message to send (max 3500 characters)
     *
     * @throws XenForo_Exception
     * @return int               The message ID
     */
    public function sendE2EText($threemaId, $message)
    {
        $this->initiate();

        try {
            /** @var Threema\MsgApi\Commands\Results\SendE2EResult $result */
            $result = $this->getE2EHelper()->sendTextMessage($threemaId, $message);
        } catch (Exception $e) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_sending_failed') . ' ' . $e->getMessage());
        }

        return $this->evaluateResult($result);
    }

    /**
     * Sends a message to a Threema ID in the preferred mode.
     *
     * Attention: You actually may want to distinguish whether a message can be/
     * has been sent in an E2E way or not as the features and of course the
     * security level differ.
     * Therefore only use this method if you do not care how your message is
     * transported or you already evaluated in which mode the message will be
     * sent.
     *
     * @param string $threemaId The id where the message should be send to
     * @param string $message   The message to send (max 3500 characters)
     *
     * @throws XenForo_Exception
     * @return int               The message ID
     */
    public function sendAuto($threemaId, $message)
    {
        // send message
        if ($this->settings->isEndToEnd()) {
            return $this->sendE2EText($threemaId, $message);
        }

        return $this->sendSimple($threemaId, $message);
    }

    /**
     * Skips the permission check. (not recommend!).
     */
    public function skipPermissionCheck()
    {
        $this->isPermissionChecked = true;
    }

    /**
     * Checks whether the user is allowed to send messages.
     *
     * @param string $threemaId
     *
     * @throws XenForo_Exception
     */
    protected function initiate(&$threemaId = null)
    {
        if (!$this->isPermissionChecked) {
            // general permission
            if (!$this->permissions->hasPermission('send')) {
                throw new XenForo_Exception(new XenForo_Phrase('threemagw_permission_error'));
            }

            // rate-limit
            // NOTE: As this check is skipped with the permission cache it may
            // happen that the limit is exceeded when more than one message is
            // sent per request, which is unlikely AFAIK.
            if ($this->permissions->isLimited('send')) {
                throw new XenForo_Exception(new XenForo_Phrase('threemagw_account_locked_due_to_high_number_of_sent_messages'));
            };

            $this->isPermissionChecked = true;
        }

        if ($threemaId) {
            $threemaId = strtoupper($threemaId);
        }
    }

    /**
     * Evaluate the result of sending the message.
     *
     * @param Threema\MsgApi\Commands\Results\SendE2EResult $result
     *
     * @throws XenForo_Exception
     */
    protected function evaluateResult($result)
    {
        if ($result->isSuccess()) {
            // log that the user has sent a message
            $this->permissions->logAction('send');

            return $result->getMessageId();
        } else {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_sending_failed') . ' ' . $result->getErrorMessage());
        }
    }
}

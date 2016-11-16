<?php
/**
 * Threema message callback used for verifying message .
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

/**
 * Listeners for custom activity when.
 */
class ThreemaGateway_Listener_TfaMessageCallback
{
    /**
     * Checks whether text messages contain code used for the receiver 2FA.
     *
     * You should set the "event hint" to "1" to only pass text messages to the
     * listener. Otherwise errors may happen.
     *
     * @param ThreemaGateway_Handler_Action_Callback      $handler
     * @param Threema\MsgApi\Helpers\ReceiveMessageResult $receiveResult
     * @param Threema\MsgApi\Messages\ThreemaMessage      $threemaMsg
     * @param array|string                                $output        [$logType, $debugLog, $publicLog]
     * @param bool                                        $saveMessage
     * @param bool                                        $debugMode
     *
     * @throws XenForo_Exception
     */
    public static function checkForReceiverCode(ThreemaGateway_Handler_Action_Callback $handler,
                                        Threema\MsgApi\Helpers\ReceiveMessageResult $receiveResult,
                                        Threema\MsgApi\Messages\ThreemaMessage $threemaMsg,
                                        &$output,
                                        &$saveMessage,
                                        $debugMode)
    {
        /** @var string $msgText the Threema message text */
        $msgText = trim($threemaMsg->getText());

        // convert number emoticons to usual numbers (just remove that uncidoe thing :)
        $msgText = str_replace(ThreemaGateway_Handler_Emoji::parseUnicode('\u20e3'), '', $msgText);

        // check whether we are responsible for the message
        // https://regex101.com/r/ttkhwd/2
        if (!preg_match('/^\d{6}$/', $msgText)) {
            return;
        }

        /** @var ThreemaGateway_Handler_Action_Receiver $receiver */
        $receiver = new ThreemaGateway_Handler_Action_Receiver;

        // first check whether message has already been saved to prevent replay attacks
        if ($receiver->messageIsReceived($receiveResult->getMessageId())) {
            throw new XenForo_Exception('Message already received!');
        }

        $handler->addLog($output, 'Recognized 2FA Reversed confirmation message.');
        $handler->addLog($output, 'Converted message text: ' . $msgText);

        // check whether we are requested to hanlde this message
        /** @var ThreemaGateway_Model_TfaPendingMessagesConfirmation $pendingRequestsModel */
        $pendingRequestsModel = XenForo_Model::create('ThreemaGateway_Model_TfaPendingMessagesConfirmation');

        /** @var array|null $pendingRequests all pending requets if there are some */
        $pendingRequests = $pendingRequestsModel->getPending(
            $handler->getRequest('from'),
            ThreemaGateway_Model_TfaPendingMessagesConfirmation::PENDING_REQUESTCODE
        );

        // handle all requests
        /** @var bool $successfullyProcessed */
        $successfullyProcessed = false;

        foreach ($pendingRequests as $id => $confirmRequest) {
            // let's first verify the receive date according to the send time
            // as this is not possible later as the send time is not logged
            if ($handler->getRequest('date') > $confirmRequest['expiry_date']) {
                $handler->addLog($output, 'Message is too old, already expired. Maximum: ' .  date('Y-m-d H:i:s', $confirmRequest['expiry_date']));
                continue;
            }
            var_dump($confirmRequest);
            // now get session data
            /** @var XenForo_Session $session */
            $session = new XenForo_Session;
            $session->start($confirmRequest['session_id']);

            $handler->addLog($output, 'Request #' .
                $confirmRequest['request_id'] . ' for session ' .
                $confirmRequest['session_id'] . ' with key ' .
                $confirmRequest['session_key'] . '.');

            /** @var array $newProviderData provider data of session */
            $newProviderData = $session->get($confirmRequest['session_key']);
            if (!$newProviderData) { //TODO: problem: often does not get session data (only pon third try or so)
                $handler->addLog($output, 'Could not get session data. Abort.');
                continue;
            }

            // finally save the received code (it is later verified whether it
            // is the correct one)
            $newProviderData['receivedCode'] = $msgText;
            $session->set($confirmRequest['session_key'], $newProviderData);
            $session->save();

            $handler->addLog($output, 'Saved code for request #' .
                $confirmRequest['request_id'] . ' for session ' .
                $confirmRequest['session_id'] . '.');

            $successfullyProcessed = true;
        }

        // if ($successfullyProcessed) {
            // do not save message as it already has been processed
            $saveMessage = false;
        // }
    }

    /**
     * Return the user model.
     *
     * @return XenForo_Model_User
     */
    protected function _getUserModel()
    {
        return XenForo_Model::create('XenForo_Model_User');
    }
}

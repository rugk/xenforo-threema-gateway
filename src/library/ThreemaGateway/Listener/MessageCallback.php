<?php
/**
 * Threema message callback.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

/**
 * Listeners for custom activity when.
 */
class ThreemaGateway_Listener_MessageCallback
{
    /**
     * Receives text messages.
     *
     * @param ThreemaGateway_Handler_Action_Callback $handler
     * @param ReceiveMessageResult                   $receiveResult
     * @param ThreemaMessage                         $threemaMsg
     * @param array                                  $output        [$logType, $debugLog, $publicLog]
     * @param bool                                   $saveMessage
     * @param bool                                   $debugMode
     *
     * @return array $hashes
     */
    public static function testCallback(ThreemaGateway_Handler_Action_Callback $handler,
                                        Threema\MsgApi\Helpers\ReceiveMessageResult $receiveResult,
                                        Threema\MsgApi\Messages\ThreemaMessage $threemaMsg,
                                        &$output,
                                        &$saveMessage,
                                        $debugMode)
    {
        // first check whether message has already been saved to prevent replay attacks
        $receiver = new ThreemaGateway_Handler_Action_Receiver;
        $messageId = $receiver->getMessageData($receiveResult->getMessageId());

        if ($messageId) {
            throw new XenForo_Exception('Message already received!');
        }

        // here you can do something with your text messages
        $saveMessage = false; // e.g. prevent saving!
    }
}

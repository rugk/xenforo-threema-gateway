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
     * Presave listener: Checks whether text messages contain code used for the receiver 2FA.
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
        /** @var bool $isError */
        $isError = true;

        // create tfa callback handler
        $class       = XenForo_Application::resolveDynamicClass('ThreemaGateway_Handler_Action_TfaCallback_TextMessage');
        $tfaCallback = new $class($handler, $receiveResult, $threemaMsg, $output, $saveMessage, $debugMode);

        // initiate
        if ($tfaCallback->prepareProcessing()) {
            $tfaCallback->setMessageTypeName('2FA Reversed confirmation message', 'code');
            $tfaCallback->setPrendingRequestType(ThreemaGateway_Model_TfaPendingMessagesConfirmation::PENDING_REQUEST_CODE);

            // convert number emoticons to usual numbers (just remove that unicode thing :)
            $tfaCallback->addFilter(
                ThreemaGateway_Handler_Action_TfaCallback_TextMessage::FILTER_REPLACE,
                [
                    ThreemaGateway_Helper_Emoji::parseUnicode('\u20e3') => ''
                ]
            );

            // check whether we are responsible for the message
            $tfaCallback->addFilter(
                ThreemaGateway_Handler_Action_TfaCallback_TextMessage::FILTER_REGEX_MATCH,
                '/^\d{6}$/' // https://regex101.com/r/ttkhwd/2
            );

            if ($tfaCallback->applyFilters()) {
                if ($tfaCallback->processPending([
                    'saveKey' => 'receivedCode'
                ])) {
                    $isError = false;
                }
            }
        }

        $tfaCallback->getReferencedData($output, $saveMessage);
        if ($isError) {
            $handler->addLog($output, __METHOD__ . ' finished with an error. No receiver code message detected.');
        }
        return;
    }

    /**
     * Presave listener: Checks whether a message has a new delivery status, which needs to be updated for the 2FA fast mode.
     *
     * You should set the "event hint" to "128" to only pass delivery receipts to the
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
    public static function checkForDeliveryReceipt(ThreemaGateway_Handler_Action_Callback $handler,
                                        Threema\MsgApi\Helpers\ReceiveMessageResult $receiveResult,
                                        Threema\MsgApi\Messages\ThreemaMessage $threemaMsg,
                                        &$output,
                                        &$saveMessage,
                                        $debugMode)
    {
        /** @var bool $isError */
        $isError = true;

        // create tfa callback handler
        $class       = XenForo_Application::resolveDynamicClass('ThreemaGateway_Handler_Action_TfaCallback_DeliveryReceipt');
        $tfaCallback = new $class($handler, $receiveResult, $threemaMsg, $output, $saveMessage, $debugMode);

        // initiate
        if ($tfaCallback->prepareProcessing()) {
            $tfaCallback->setMessageTypeName('2FA Fast acknowledge message', 'delivery receipt');
            $tfaCallback->setPrendingRequestType(ThreemaGateway_Model_TfaPendingMessagesConfirmation::PENDING_REQUEST_DELIVERY_RECEIPT);

            if ($tfaCallback->applyFilters()) {
                if ($tfaCallback->processPending([
                    'saveKey'                      => 'receivedCode',
                    'saveKeyReceiptType'           => 'receivedDeliveryReceipt',
                    'saveKeyReceiptTypeLargest'    => 'receivedDeliveryReceiptLargest',
                    'tfaProviderCallbackOnDecline' => 'ThreemaGateway_Tfa_Fast',
                    'tfaProviderId'                => ThreemaGateway_Constants::TfaIDprefix . '_fast',
                ])) {
                    $isError = false;
                }
            }
        }

        $tfaCallback->getReferencedData($output, $saveMessage);
        if ($isError) {
            $handler->addLog($output, __METHOD__ . ' finished with an error. No delivery receipt of 2FA mode');
        }
        return;
    }
}

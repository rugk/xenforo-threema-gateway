<?php
/**
 * Allows one to get received messages.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Handler_Action_Receiver extends ThreemaGateway_Handler_Action_Abstract
{
    /**
     * @var XenForo_Input raw parameters
     */
    protected $input;

    /**
     * @var array filtered parameters
     */
    protected $filtered;

    /**
     * Check whether a specific message has been received and returns it.
     *
     * @param string $senderId The ID where you expect a message from.
     * @param string $keyword  (optional) A keyword you look for.
     *
     * @throws XenForo_Exception
     * @return ???
     */
    public function getMessage($senderId, $keyword = null)
    {
        // check permission
        if (!$this->permissions->hasPermission('receive')) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_permission_error'));
        }
    }

    /**
     * Initializes handling for processing a request callback.
     *
     * @param Zend_Controller_Request_Http $request
     */
    public function initCallbackHandling(Zend_Controller_Request_Http $request)
    {
        $this->request = $request;
        $this->input   = new XenForo_Input($request);

        $this->filtered = $this->input->filter([
            'accesstoken' => XenForo_Input::STRING,
            'from' => XenForo_Input::STRING,
            'to' => XenForo_Input::STRING,
            'messageId' => XenForo_Input::STRING,
            'date' => XenForo_Input::DATE_TIME,
            'nonce' => XenForo_Input::STRING,
            'box' => XenForo_Input::STRING,
            'mac' => XenForo_Input::STRING
        ]);

        // var_dump($this->filtered);
    }

    /**
     * Validates the callback request. In case of failure let Gateway server
     * retry.
     *
     * @param string $errorString Output error string
     *
     * @return bool
     */
    public function validateRequest(&$errorString)
    {
        // access token validation
        /* @var XenForo_Options */
        $options = XenForo_Application::getOptions();
        if (!$options->threema_gateway_receivecallback) {
            $errorString = 'Unverified request';
            return false;
        }

        if (!$this->getCryptTool()->stringCompare(
            $options->threema_gateway_receivecallback,
            $this->filtered['accesstoken']
            )) {
            $errorString = 'Unverified request';
            return false;
        }

        // HMAC validation
        // (retrying allowed as messages would otherwise get lost when
        // the secret is changed)
        if (!$this->getE2EHelper()->checkMac(
            $this->filtered['from'],
            $this->filtered['to'],
            $this->filtered['messageId'],
            $this->filtered['date'],
            $this->filtered['nonce'],
            $this->filtered['box'],
            $this->filtered['mac'],
            $this->settings->getSecret()
            )) {
            $errorString = 'Unverified request';
            return false;
        }

        return true;
    }

    /**
     * Validates the callback request. In case of failure let Gateway server
     * should not retry here as it likely would not help anyway.
     *
     * @param string $errorString Output error string
     *
     * @return bool
     */
    public function validatePreConditions(&$errorString)
    {
        // simple, formal validation
        if (!$this->getCryptTool()->stringCompare($this->filtered['to'], $this->settings->getId())) {
            $errorString = 'Invalid request';
            return false;
        }

        return true;
    }

    /**
     * Receive the message, decrypt it and save it.
     *
     * @param string $downloadPath The directory where to store received files
     * @param bool   $debugMode    Whether debugging information should be returned
     *                             (default: false)
     *
     * @return string the message, which should be shown
     */
    public function processMessage($downloadPath, $debugMode = false)
    {
        $output = '';

        if (!ThreemaGateway_Handler_Validation::checkDir($downloadPath)) {
            throw new XenForo_Exception('Download dir ' . $downloadPath . ' cannot be accessed.');
        }

        try {
            /* ReceiveMessageResult */
            $receiveResult = $this->getE2EHelper()->receiveMessage(
                $this->filtered['from'],
                $this->filtered['messageId'],
                $this->getCryptTool()->hex2bin($this->filtered['box']),
                $this->getCryptTool()->hex2bin($this->filtered['nonce']),
                $downloadPath
            );
        } catch (Exception $e) {
            // as XenForo does not allow Exception chaing, we better log the exception right now
            XenForo_Error::logException($e);
            throw new Exception('Message cannot be processed: ' . $e->getMessage());
        }

        if (!$receiveResult->isSuccess()) {
            throw new XenForo_Exception('Message cannot be processed.' . implode('|', $receiveResult->getErrors()));
        }

        /* @var ThreemaMessage */
        $threemaMsg = $receiveResult->getThreemaMessage();

        if ($debugMode) {
            $EOL        = PHP_EOL;

            $output  = 'New message from ' . $this->filtered['from'] . $EOL . $EOL;
            $output .= 'ID: ' . $receiveResult->getMessageId() . $EOL;
            $output .= 'message.type: ' . $threemaMsg->getTypeCode() . ' (' . $threemaMsg . ')' . $EOL;
            $output .= 'files: ' . implode('|', $receiveResult->getFiles()) . $EOL;

            if ($threemaMsg instanceof Threema\MsgApi\Messages\TextMessage) {
                $output .= 'message.getText: ' . $threemaMsg->getText() . $EOL;
            }
            if ($threemaMsg instanceof Threema\MsgApi\Messages\DeliveryReceipt) {
                $output .= 'message.getReceiptType: ' . $threemaMsg->getReceiptType() . $EOL;
                $output .= 'message.getReceiptTypeName: ' . $threemaMsg->getReceiptTypeName() . $EOL;
            }
            if ($threemaMsg instanceof Threema\MsgApi\Messages\FileMessage) {
                $output .= 'message.getBlobId: ' . $threemaMsg->getBlobId() . $EOL;
                $output .= 'message.receipttypename: ' . $threemaMsg->getEncryptionKey() . $EOL;
                $output .= 'message.getFilename: ' . $threemaMsg->getFilename() . $EOL;
                $output .= 'message.getMimeType: ' . $threemaMsg->getMimeType() . $EOL;
                $output .= 'message.getSize: ' . $threemaMsg->getSize() . $EOL;
                $output .= 'message.getThumbnailBlobId: ' . $threemaMsg->getThumbnailBlobId() . $EOL;
            }
            if ($threemaMsg instanceof Threema\MsgApi\Messages\ImageMessage) {
                $output .= 'message.getBlobId: ' . $threemaMsg->getBlobId() . $EOL;
                $output .= 'message.getLength: ' . $threemaMsg->getLength() . $EOL;
                $output .= 'message.getNonce: ' . $threemaMsg->getNonce() . $EOL;
            }
        }

        return $output;
    }
}

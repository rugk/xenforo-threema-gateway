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
    }

    /**
     * Validates the callback request. In case of failure the Gateway server
     * should not retry here as it likely would not help anyway.
     *
     * This validation is only a basic validation and does not handle with any
     * potentially secret data to prevent any exposures.
     * It makes sure malwformed requests can be denied fastly without needing
     * to check the authentity/security of the message.
     *
     * @param string $errorString Output error string
     *
     * @return bool
     */
    public function validatePreConditions(&$errorString)
    {
        // special error message to let users know not to forget the access
        // token
        if (!$this->input->inRequest('accesstoken')) {
            $errorString = 'Access token missing';
            return false;
        };

        // check for other missing parameters
        if (!$this->input->inRequest('from') ||
            !$this->input->inRequest('to') ||
            !$this->input->inRequest('messageId') ||
            !$this->input->inRequest('date') ||
            !$this->input->inRequest('nonce') ||
            !$this->input->inRequest('box') ||
            !$this->input->inRequest('mac')
        ) {
            $errorString = [null, 'Invalid request: parameter missing', 'Invalid request'];
            return false;
        };

        $settings = new ThreemaGateway_Handler_Settings;
        if (!$settings->isEndtoEnd()) {
            $errorString = [null, 'Receiving messages is not supported, end to end mode is not configured (correctly)', 'Receiving messages is not supported'];
            return false;
        }

        return true;
    }

    /**
     * Validates the callback request's authenticy and integrity. In case of
     * failure the Gateway server SHOULD retry.
     *
     * This validates the integrity of the request and the authentity that the
     * calling instance actually is the Threema Gateway server.
     * Retrying is allowed as the secrets, which are used to validate the
     * request may change at any time and to avoid a loss of messages the
     * Gateway server is supposed to retry the delivery.
     *
     * @param string $errorString Output error string
     *
     * @return bool
     */
    public function validateRequest(&$errorString)
    {
        // access token validation (authentication of Gateway server)
        /* @var XenForo_Options */
        $options = XenForo_Application::getOptions();
        if (!$options->threema_gateway_receivecallback) {
            $errorString = [null, 'Unverified request: access token is not configured', 'Unverified request'];
            return false;
        }

        if (!$this->getCryptTool()->stringCompare(
            $options->threema_gateway_receivecallback,
            $this->filtered['accesstoken']
        )) {
            $errorString = [null, 'Unverified request: access token invalid', 'Unverified request'];
            return false;
        }

        // HMAC validation (verifies integrity of request)
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
            $errorString = [null, 'Unverified request: HMAC verification failed', 'Unverified request'];
            return false;
        }

        return true;
    }

    /**
     * Validates the callback request formally. In case of failure the Gateway
     * server should NOT retry, as it likely would not help anyway.
     *
     * This validation is only a formal validation of the request data. The
     * request should already have been validated ({@see validateRequest()}).
     * In contrast to the basic validation ({@see validatePreConditions()}) this
     * validation deals with secret data and furthermore assures that the send
     * request is valid.
     * It is used to prevent malformed (but verified) bad requests to get to the
     * decryption part, whcih cannot decrypt them anyway.
     *
     * @param string $errorString Output error string
     *
     * @return bool
     */
    public function validateFormalities(&$errorString)
    {
        // simple, formal validation of Gateway ID
        if (!$this->getCryptTool()->stringCompare($this->filtered['to'], $this->settings->getId())) {
            $errorString = [null, 'Invalid request: formal verification failed', 'Invalid request'];
            return false;
        }

        /* @var XenForo_Options */
        $options = XenForo_Application::getOptions();
        $rejectOld = false;
        if ($options->threema_gateway_verify_receive_time && $options->threema_gateway_verify_receive_time['enabled']) {
            $rejectOld = $options->threema_gateway_verify_receive_time['time'];
        } else {
            // fallback to 14 days
            $rejectOld = '-14 days';
        }

        // discard too old messages
        if ($this->filtered['date'] < strtotime($rejectOld)) {
            $errorString = [null, 'Message cannot be processed: Message is too old', 'Message cannot be processed'];
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
            // as XenForo does not allow Exception chaining, we better log the exception right now
            XenForo_Error::logException($e);
            throw new Exception('Message cannot be processed: [' . get_class($e) . '] ' . $e->getMessage());
        }

        if (!$receiveResult->isSuccess()) {
            throw new XenForo_Exception('Message cannot be processed: [ResultErrors] ' . implode('|', $receiveResult->getErrors()));
        }

        /* @var ThreemaMessage */
        $threemaMsg = $receiveResult->getThreemaMessage();

        if ($debugMode) {
            $EOL        = PHP_EOL;

            // common heading
            $publicLog  = 'New message from ' . $this->filtered['from'] . $EOL . $EOL;
            $publicLog .= 'ID: ' . $receiveResult->getMessageId() . $EOL;
            $publicLog .= 'message.type: ' . $threemaMsg->getTypeCode() . ' (' . $threemaMsg . ')' . $EOL;
            $debugLog = $publicLog;
            $publicLog .= '[...]' . $EOL;

            // secret part of heading
            $debugLog .= 'files: ' . implode('|', $receiveResult->getFiles()) . $EOL;

            // detailed result (is secret)
            if ($threemaMsg instanceof Threema\MsgApi\Messages\TextMessage) {
                $debugLog .= 'message.getText: ' . $threemaMsg->getText() . $EOL;
            }
            if ($threemaMsg instanceof Threema\MsgApi\Messages\DeliveryReceipt) {
                $debugLog .= 'message.getReceiptType: ' . $threemaMsg->getReceiptType() . $EOL;
                $debugLog .= 'message.getReceiptTypeName: ' . $threemaMsg->getReceiptTypeName() . $EOL;
            }
            if ($threemaMsg instanceof Threema\MsgApi\Messages\FileMessage) {
                $debugLog .= 'message.getBlobId: ' . $threemaMsg->getBlobId() . $EOL;
                $debugLog .= 'message.getEncryptionKey: ' . $threemaMsg->getEncryptionKey() . $EOL;
                $debugLog .= 'message.getFilename: ' . $threemaMsg->getFilename() . $EOL;
                $debugLog .= 'message.getMimeType: ' . $threemaMsg->getMimeType() . $EOL;
                $debugLog .= 'message.getSize: ' . $threemaMsg->getSize() . $EOL;
                $debugLog .= 'message.getThumbnailBlobId: ' . $threemaMsg->getThumbnailBlobId() . $EOL;
            }
            if ($threemaMsg instanceof Threema\MsgApi\Messages\ImageMessage) {
                $debugLog .= 'message.getBlobId: ' . $threemaMsg->getBlobId() . $EOL;
                $debugLog .= 'message.getLength: ' . $threemaMsg->getLength() . $EOL;
                $debugLog .= 'message.getNonce: ' . $threemaMsg->getNonce() . $EOL;
            }

            $output = [null, $debugLog, $publicLog];
        }

        return $output;
    }
}

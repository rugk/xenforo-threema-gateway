<?php
/**
 * Allows XenForo to receive Threema messages by providing a callback.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Handler_Action_Callback extends ThreemaGateway_Handler_Action_Abstract
{
    /**
     * @var string used by strtotime to allow messages sent in the future
     */
    const ALLOW_FUTURE_MESSAGE_TIME = '+5 sec';

    /**
     * @var XenForo_Input raw parameters
     */
    protected $input;

    /**
     * @var array filtered parameters
     */
    protected $filtered;

    /**
     * @var bool whether it has been checked that the message is not used in a
     *           replay attack
     */
    protected $messageReplayChecked = false;

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
     * @param string|array $errorString Output error string/array
     *
     * @return bool
     */
    public function validatePreConditions(&$errorString)
    {
        /** @var XenForo_Options $options */
        $options = XenForo_Application::getOptions();

        // only allow POST requests (unless GET is allowed in ACP)
        if (!$this->settings->isDebug() || !$options->threema_gateway_allow_get_receive) {
            // as an exception we access the superglobal directly here as it is
            // difficult to get the request object
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $errorString = [null, 'No POST request.', ''];
                return false;
            };
        }

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

        if (!$this->settings->isEndtoEnd()) {
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
     * @param string|array $errorString Output error string/array
     *
     * @return bool
     */
    public function validateRequest(&$errorString)
    {
        // access token validation (authentication of Gateway server)
        /** @var XenForo_Options $options */
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
     * decryption part, which cannot decrypt them anyway.
     *
     * @param string|array $errorString Output error string/array
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

        /** @var XenForo_Options $options */
        $options   = XenForo_Application::getOptions();
        /** @var string $rejectOld the maximum age of a message; default/fallback: 14 days */
        $rejectOld = '-14 days';
        if ($options->threema_gateway_verify_receive_time && $options->threema_gateway_verify_receive_time['enabled']) {
            $rejectOld = $options->threema_gateway_verify_receive_time['time'];
        }

        // discard too old messages
        if ($this->filtered['date'] < strtotime($rejectOld, XenForo_Application::$time)) {
            $errorString = [null, 'Message cannot be processed: Message is too old (send at ' . date('Y-m-d H:i:s', $this->filtered['date']) . ', messages older than ' . $rejectOld . ' are rejected)', 'Message cannot be processed'];
            return false;
        }

        // discard messages sent in the future
        // (to handle leap seconds or so we allow some seconds difference)
        if ($this->filtered['date'] > strtotime(self::ALLOW_FUTURE_MESSAGE_TIME, XenForo_Application::$time)) {
            $errorString = [null, 'Message cannot be processed: Message is send in the future (send at ' . date('Y-m-d H:i:s', $this->filtered['date']) . ', please check your server clock)', 'Message cannot be processed'];
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
     * @throws XenForo_Exception
     * @return string|array      the message, which should be shown
     */
    public function processMessage($downloadPath, $debugMode = false)
    {
        /** @var string $output */
        $output = '';

        if (!ThreemaGateway_Handler_Validation::checkDir($downloadPath)) {
            throw new XenForo_Exception('Download dir ' . $downloadPath . ' cannot be accessed.');
        }

        try {
            /** @var Threema\MsgApi\Helpers\ReceiveMessageResult $receiveResult */
            $receiveResult = $this->getE2EHelper()->receiveMessage(
                $this->filtered['from'],
                $this->filtered['messageId'],
                $this->getCryptTool()->hex2bin($this->filtered['box']),
                $this->getCryptTool()->hex2bin($this->filtered['nonce']),
                $downloadPath
            );
        } catch (Exception $e) {
            // as XenForo does not allow exception chaining, we better log the exception right now
            XenForo_Error::logException($e);
            throw new XenForo_Exception('Message cannot be processed: [' . get_class($e) . '] ' . $e->getMessage());
        }

        if (!$receiveResult->isSuccess()) {
            throw new XenForo_Exception('Message cannot be processed: [ResultErrors] ' . implode('|', $receiveResult->getErrors()));
        }

        /** @var Threema\MsgApi\Messages\ThreemaMessage $threemaMsg */
        $threemaMsg = $receiveResult->getThreemaMessage();

        // create detailed log when debug mode is enabled
        if ($debugMode) {
            $output = $this->getLogData($receiveResult, $threemaMsg);
        }

        /** @var bool $saveMessage whether to save the message to DB. */
        $saveMessage = true;

        XenForo_CodeEvent::fire('threemagw_message_callback_presave', [
            $this,
            $receiveResult,
            $threemaMsg,
            &$output,
            &$saveMessage,
            $debugMode
        ], $threemaMsg->getTypeCode());

        // save message in database
        try {
            if ($saveMessage) {
                $this->saveMessage($receiveResult, $threemaMsg);
            } else {
                $this->saveMessageId($receiveResult->getMessageId());
            }
        } catch (Exception $e) {
            // as XenForo does not allow Exception chaining, we better log the exception right now
            XenForo_Error::logException($e);
            throw new XenForo_Exception('Message could not be saved: [' . get_class($e) . '] ' . $e->getMessage());
        }

        XenForo_CodeEvent::fire('threemagw_message_callback_postsave', [
            $this,
            $receiveResult,
            $threemaMsg,
            &$output,
            $saveMessage,
            $debugMode
        ], $threemaMsg->getTypeCode());

        return $output;
    }

    /**
     * Adds a string to the current log string or array.
     *
     * @param array|string $log              string or array
     * @param string       $stringToAdd
     * @param string       $stringToAddDetail
     */
    public function addLog(&$log, $stringToAdd, $stringToAddDetail = null)
    {
        // convert to array if necessary or just add string
        if (is_string($log)) {
            if ($stringToAddDetail) {
                $log[1] = $log;
                $log[2] = $log;
            } else {
                $log .= PHP_EOL . $stringToAdd;
                return;
            }
        }

        // add to array
        if ($stringToAddDetail) {
            $log[1] .= PHP_EOL . $stringToAddDetail;
        } elseif ($stringToAdd) {
            $log[1] .= PHP_EOL . $stringToAdd;
        }

        if ($stringToAdd) {
            $log[2] .= PHP_EOL . $stringToAdd;
        }
    }

    /**
     * Checks whether a message is already saved. If so this may indicate a
     * replay attack.
     *
     * @param string $messageId
     *
     * @throws XenForo_Exception
     */
    public function assertNoReplayAttack($messageId)
    {
        // do not check multiple times
        if ($this->messageReplayChecked) {
            return;
        }

        // skip all internal handling of receiver as it does a simple yes/no check only
        // also skip permissions as currently no user is logged in
        /** @var ThreemaGateway_Handler_Action_Receiver $receiver */
        $receiver = new ThreemaGateway_Handler_Action_Receiver(true, true);

        // check whether message has already been saved to prevent replay attacks
        if ($receiver->messageIsReceived($messageId)) {
            throw new XenForo_Exception('Message "' . $messageId . '" has already been received and is already saved. This may indicate a replay attack.');
        }

        $this->messageReplayChecked = true;
    }

    /**
     * Get request data.
     *
     * If you obmit the $key parameter you get an array of all request parameters.
     * If not, you'll get one specific entry.
     * In case nothing could be found, this returns "null".
     *
     * @param  string            $key
     * @return string|array|null
     */
    public function getRequest($key = null)
    {
        if ($key === null) {
            return $this->filtered;
        }

        if (array_key_exists($key, $this->filtered)) {
            return $this->filtered[$key];
        }

        return null;
    }

    /**
     * Returns the original input.
     *
     * It is strongly discouraghed to use this and better use {@see getRequest()}
     * as this data is already filtered.
     * In general you should have few real reasons to get this RAW data.
     *
     * @return XenForo_Input
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Returns an array with a not so detailed[2] and a very detailed[1] log
     * of the received message.
     *
     * @param Threema\MsgApi\Helpers\ReceiveMessageResult $receiveResult Threema MsgApi receive result
     * @param Threema\MsgApi\Messages\ThreemaMessage      $threemaMsg    Threema MsgApi message
     *
     * @return array[null, string, string]
     */
    protected function getLogData(
        Threema\MsgApi\Helpers\ReceiveMessageResult $receiveResult,
        Threema\MsgApi\Messages\ThreemaMessage $threemaMsg
    ) {
        $eol = PHP_EOL;

        // common heading
        $publicLog  = 'New message from ' . $this->filtered['from'] . $eol . $eol;
        $publicLog .= 'ID: ' . $receiveResult->getMessageId() . $eol;
        $publicLog .= 'message.type: ' . $threemaMsg->getTypeCode() . ' (' . $threemaMsg . ')' . $eol;
        $publicLog .= 'message.date: ' . date('Y-m-d H:i:s', $this->filtered['date']) . $eol;
        $debugLog = $publicLog;
        $publicLog .= '[...]' . $eol;

        // secret part of heading
        $debugLog .= 'files: ' . implode('|', $receiveResult->getFiles()) . $eol;
        // NOTE: File type (key of array) is not logged here!

        // detailed result (is secret)
        if ($threemaMsg instanceof Threema\MsgApi\Messages\TextMessage) {
            $debugLog .= 'message.getText: ' . $threemaMsg->getText() . $eol;
        }
        if ($threemaMsg instanceof Threema\MsgApi\Messages\DeliveryReceipt) {
            $debugLog .= 'message.getReceiptType: ' . $threemaMsg->getReceiptType() . $eol;
            $debugLog .= 'message.getReceiptTypeName: ' . $threemaMsg->getReceiptTypeName() . $eol;
            $debugLog .= 'message.getAckedMessageIds: ' . implode('|', $this->bin2hexArray($threemaMsg->getAckedMessageIds())) . $eol;
        }
        if ($threemaMsg instanceof Threema\MsgApi\Messages\FileMessage) {
            $debugLog .= 'message.getBlobId: ' . $threemaMsg->getBlobId() . $eol;
            $debugLog .= 'message.getEncryptionKey: ' . $threemaMsg->getEncryptionKey() . $eol;
            $debugLog .= 'message.getFilename: ' . $threemaMsg->getFilename() . $eol;
            $debugLog .= 'message.getMimeType: ' . $threemaMsg->getMimeType() . $eol;
            $debugLog .= 'message.getSize: ' . $threemaMsg->getSize() . $eol;
            $debugLog .= 'message.getThumbnailBlobId: ' . $threemaMsg->getThumbnailBlobId() . $eol;
        }
        if ($threemaMsg instanceof Threema\MsgApi\Messages\ImageMessage) {
            $debugLog .= 'message.getBlobId: ' . $threemaMsg->getBlobId() . $eol;
            $debugLog .= 'message.getLength: ' . $threemaMsg->getLength() . $eol;
            $debugLog .= 'message.getNonce: ' . $this->getCryptTool()->bin2hex($threemaMsg->getNonce()) . $eol;
        }

        return [null, $debugLog, $publicLog];
    }

    /**
     * Saves a decrypted message in the database.
     *
     * @param Threema\MsgApi\Helpers\ReceiveMessageResult $receiveResult Threema MsgApi receive result
     * @param Threema\MsgApi\Messages\ThreemaMessage      $threemaMsg    Threema MsgApi message
     *
     * @throws XenForo_Exception
     */
    protected function saveMessage(
        Threema\MsgApi\Helpers\ReceiveMessageResult $receiveResult,
        Threema\MsgApi\Messages\ThreemaMessage $threemaMsg
    ) {
        $dataWriter = XenForo_DataWriter::create('ThreemaGateway_DataWriter_Messages');

        $dataWriter->set('message_id', $receiveResult->getMessageId()); // this is set for all tables
        $dataWriter->set('message_type_code', $threemaMsg->getTypeCode(), ThreemaGateway_Model_Messages::DB_TABLE_MESSAGES);
        $dataWriter->set('sender_threema_id', $this->filtered['from'], ThreemaGateway_Model_Messages::DB_TABLE_MESSAGES);
        $dataWriter->set('date_send', $this->filtered['date'], ThreemaGateway_Model_Messages::DB_TABLE_MESSAGES);
        // $dataWriter->set('date_received', XenForo_Application::$time); //= default

        // files
        if (count($receiveResult->getFiles()) >= 1) {
            /** @var array $fileList the files associated to the message */
            $fileList = $receiveResult->getFiles();
            // set current (first) type/path
            $dataWriter->set('file_type', key($fileList), ThreemaGateway_Model_Messages::DB_TABLE_FILES);
            $dataWriter->set('file_path', $dataWriter->normalizeFilePath(current($fileList)), ThreemaGateway_Model_Messages::DB_TABLE_FILES);
            // remove current value from array
            unset($fileList[key($fileList)]);
            // pass as extra data for later saving
            $dataWriter->setExtraData(ThreemaGateway_DataWriter_Messages::DATA_FILES, $fileList);
        }

        // set values for each message type
        if ($threemaMsg instanceof Threema\MsgApi\Messages\TextMessage) {
            $dataWriter->set('text', $threemaMsg->getText(), ThreemaGateway_Model_Messages::DB_TABLE_MESSAGES . '_text');
        } elseif ($threemaMsg instanceof Threema\MsgApi\Messages\DeliveryReceipt) {
            $dataWriter->set('receipt_type', $threemaMsg->getReceiptType(), ThreemaGateway_Model_Messages::DB_TABLE_MESSAGES . '_delivery_receipt');

            /** @var array $ackedMsgIds the acknowledged message IDs */
            $ackedMsgIds = $this->bin2hexArray($threemaMsg->getAckedMessageIds());
            if (count($ackedMsgIds) >= 1) {
                // set current (first) type/path
                $dataWriter->set('ack_message_id', $ackedMsgIds[0], ThreemaGateway_Model_Messages::DB_TABLE_DELIVERY_RECEIPT);
                // remove current value from array
                unset($ackedMsgIds[0]);
                // pass as extra data for later saving
                $dataWriter->setExtraData(ThreemaGateway_DataWriter_Messages::DATA_ACKED_MSG_IDS, $ackedMsgIds);
            }
        } elseif ($threemaMsg instanceof Threema\MsgApi\Messages\FileMessage) {
            $dataWriter->set('file_size', $threemaMsg->getSize(), ThreemaGateway_Model_Messages::DB_TABLE_MESSAGES . '_file');
            $dataWriter->set('file_name', $threemaMsg->getFilename(), ThreemaGateway_Model_Messages::DB_TABLE_MESSAGES . '_file');
            $dataWriter->set('mime_type', $threemaMsg->getMimeType(), ThreemaGateway_Model_Messages::DB_TABLE_MESSAGES . '_file');
        } elseif ($threemaMsg instanceof Threema\MsgApi\Messages\ImageMessage) {
            $dataWriter->set('file_size', $threemaMsg->getLength(), ThreemaGateway_Model_Messages::DB_TABLE_MESSAGES . '_image');
        }

        return $dataWriter->save();
    }

    /**
     * Only saves a message ID to the database to prevent replay attacks.
     *
     * @param string $messageId
     *
     * @throws XenForo_Exception
     */
    protected function saveMessageId($messageId)
    {
        $dataWriter = XenForo_DataWriter::create('ThreemaGateway_DataWriter_Messages');

        $dataWriter->set('message_id', $messageId, ThreemaGateway_Model_Messages::DB_TABLE_MESSAGES);
        $dataWriter->roundReceiveDate(); // reduce amount of meta data stored

        return $dataWriter->save();
    }

    /**
     * Converts binary data in an array to hex.
     *
     * @param array $bin binary array
     *
     * @return array
     */
    protected function bin2hexArray($bin)
    {
        $output = [];
        foreach ($bin as $item) {
            $output[] = $this->getCryptTool()->bin2hex($item);
        }
        return  $output;
    }
}

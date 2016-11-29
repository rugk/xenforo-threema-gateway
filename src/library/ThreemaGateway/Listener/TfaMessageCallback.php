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
     * @var array Cache of models
     */
    static $modelCache = [];

    /**
     * @var XenForo_Session Imitiated session
     */
    static $session;

    /**
     * @var array user data cache
     */
    static $user = [];

    /**
     * @var string How the provider data has been fetched
     */
    static $providerDataFetchMethod;

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

        // first check whether message has already been saved to prevent replay attacks
        $handler->assertNoReplayAttack($receiveResult->getMessageId());


        $handler->addLog($output, 'Recognized 2FA Reversed confirmation message.');
        $handler->addLog($output, '[...]', 'Converted message text: ' . $msgText);

        // check whether we are requested to handle this message
        /** @var ThreemaGateway_Model_TfaPendingMessagesConfirmation $pendingRequestsModel */
        $pendingRequestsModel = XenForo_Model::create('ThreemaGateway_Model_TfaPendingMessagesConfirmation');

        /** @var array|null $pendingRequests all pending requets if there are some */
        $pendingRequests = $pendingRequestsModel->getPending(
            $handler->getRequest('from'),
            null,
            ThreemaGateway_Model_TfaPendingMessagesConfirmation::PENDING_REQUESTCODE
        );

        if (!$pendingRequests) {
            $handler->addLog($output, 'No confirmation requests registered. Abort.');
            return;
        }

        // handle all requests
        /** @var bool $successfullyProcessed */
        $successfullyProcessed = false;

        foreach ($pendingRequests as $id => $confirmRequest) {
            // let's first verify the receive date according to the send time
            // as this is not possible later as the send time is not logged
            if ($handler->getRequest('date') > $confirmRequest['expiry_date']) {
                $handler->addLog($output,
                    'Message is too old.',
                    'Message is too old, already expired. Maximum: ' .  date('Y-m-d H:i:s', $confirmRequest['expiry_date'])
                );
                continue;
            }
            // the check with the current time is done in the actual 2FA provider (verifyFromInput)

            $handler->addLog($output, '', 'Request #' .
                $confirmRequest['request_id'] . ' from ' .
                $confirmRequest['provider_id'] . ' for user ' .
                $confirmRequest['user_id'] . ' for session ' .
                $confirmRequest['session_id']);

            /** @var array $providerData provider data of session */
            $providerData = [];
            try {
                $providerData = self::getProviderDataBySession($confirmRequest);

                $handler->addLog($output,
                    '',
                    'Got provider data from session.'
                );
            } catch (Exception $e) {
                $handler->addLog($output,
                    '',
                    $e->getMessage() . ' Try 2FA model.'
                );

                // second try via model
                try {
                    $providerData = self::getProviderDataByModel($confirmRequest);

                    $handler->addLog($output,
                        '',
                        'Got provider data from user model.'
                    );
                } catch (Exception $e) {
                    $handler->addLog($output,
                        'Could not get provider data.',
                        $e->getMessage() . ' Abort.'
                    );
                    continue;
                }
            }

            // finally save the received code
            $providerData['receivedCode'] = $msgText;
            // whether the code is the same as the requested one is verified in
            // the actual 2FA provider (verifyFromInput) later

            try {
                self::saveProviderData($providerData, $confirmRequest);
            } catch (Exception $e) {
                $handler->addLog($output, 'Could not save provider data.', $e->getMessage());
                continue;
            }

            $handler->addLog($output, 'Code saved.', 'Saved code for request #' .
                $confirmRequest['request_id'] . ' from' .
                $confirmRequest['provider_id'] . ' for user ' .
                $confirmRequest['user_id'] . ' for session ' .
                $confirmRequest['session_id']);

            $successfullyProcessed = true;
        }

        if ($successfullyProcessed) {
            // do not s<ave message as it already has been processed
            $saveMessage = false;
        }
    }

    /**
     * Fetches and returns the provider data using the session.
     *
     * @param array $confirmRequest the confirmation message request
     * @throws XenForo_Exception
     * @return array
     */
    protected static function getProviderDataBySession(array $confirmRequest)
    {
        /** @var XenForo_Session $session */
        $session = self::getSession();
        $session->threemagwSetupRaw($confirmRequest['session_id'], false);

        /** @var string $sessionKey session key identifying  */
        $sessionKey = 'tfaData_' . $confirmRequest['provider_id'];
        /** @var array $providerData provider data of session */
        $providerData = $session->get($sessionKey);

        if (empty($providerData)) {
            throw new XenForo_Exception('Could not get provider data from session using key ' . $sessionKey . '.');
        }

        self::$providerDataFetchMethod = 'session';
        return $providerData;
    }

    /**
     * Fetches and returns the provider data.
     *
     * @param array $confirmRequest the confirmation message request
     * @throws XenForo_Exception
     * @return array
     */
    protected static function getProviderDataByModel(array $confirmRequest)
    {
        /** @var XenForo_Model_Tfa $tfaModel */
        $tfaModel = self::getModelFromCache('XenForo_Model_Tfa');

        /** @var array $userTfa */
        $userTfa = $tfaModel->getUserTfaEntries($confirmRequest['user_id']);
        if (!$userTfa) {
            throw new XenForo_Exception('Could not get user 2FA data.');
        }

        try {
            /** @var array $providerData provider data of session */
            $providerData = unserialize($userTfa[$confirmRequest['provider_id']]['provider_data']);
        } catch (Exception $e) {
            throw new XenForo_Exception('Could not get provider data. (error: ' . $e->getMessage() . ')');
        }

        if (empty($providerData)) {
            throw new XenForo_Exception('Could not get provider data.');
        }

        self::$providerDataFetchMethod = 'tfa_model';
        return $providerData;
    }

    /**
     * Gets model from cache or initializes a new model if needed.
     *
     * @param array $newProviderData porovider data to save
     * @param array $confirmRequest the confirmation message request
     *
     * @throws XenForo_Exception
     */
    protected static function saveProviderData(array $newProviderData, array $confirmRequest)
    {
        if (self::$providerDataFetchMethod == 'session') {
            /** @var string $sessionKey session key identifying  */
            $sessionKey = 'tfaData_' . $confirmRequest['provider_id'];

            /** @var XenForo_Session $session */
            $session = self::getSession();

            $session->set($sessionKey, $newProviderData);
            $session->save();
        } elseif (self::$providerDataFetchMethod == 'tfa_model') {
            /** @var XenForo_Model_Tfa $tfaModel */
            $tfaModel = self::getModelFromCache('XenForo_Model_Tfa');
            $tfaModel->updateUserProvider($confirmRequest['user_id'], $confirmRequest['provider_id'], $newProviderData, false);
        } else {
            throw new XenForo_Exception('Invalid provider data fetch method: ' . self::$providerDataFetchMethod);
        }
    }

    /**
     * Gets model from cache or initializes a new model if needed.
     *
     * @param string $class Name of class to load
     *
     * @return XenForo_Model
     */
    protected static function getModelFromCache($class)
    {
        if (!isset(self::$modelCache[$class]))
        {
            self::$modelCache[$class] = XenForo_Model::create($class);
        }

        return self::$modelCache[$class];
    }

    /**
     * Returns the XenForo session.
     *
     * @return XenForo_Session
     */
    protected static function getSession()
    {
        if (!isset(self::$session))
        {
            $class = XenForo_Application::resolveDynamicClass('XenForo_Session');
            self::$session = new $class;
        }

        return self::$session;
    }

    /**
     * Returns the user array.
     *
     * @param int $userId
     * @return array
     */
    protected static function getUserData($userId)
    {
        if (!isset(self::$user[$userId]))
        {
            /** @var XenForo_Model_User $userModel */
            $userModel = self::getModelFromCache('XenForo_Model_User');
            /** @var array $user */
            $user = $userModel->getFullUserById($userId);
            if (!$user) {
                throw new XenForo_Exception('Could not get user data data.');
            }

            self::$user[$userId] = $user;
        }

        return self::$user[$userId];
    }
}

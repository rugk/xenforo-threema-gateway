<?php
/**
 * 2FA callback actions.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

abstract class ThreemaGateway_Handler_Action_TfaCallback_Abstract extends ThreemaGateway_Handler_Action_Abstract
{
    /**
     * @var array Cache of models
     */
    protected $modelCache = [];

    /**
     * @var XenForo_Session|null Imitiated session
     */
    protected $session = null;

    /**
     * @var array user data cache
     */
    protected $user = [];

    /**
     * @var string how the provider data has been fetched
     */
    protected $dataFetchMode;

    /**
     * @var ThreemaGateway_Handler_Action_Callback
     */
    protected $callback;

    /**
     * @var Threema\MsgApi\Helpers\ReceiveMessageResult
     */
    protected $receiveResult;

    /**
     * @var Threema\MsgApi\Messages\ThreemaMessage
     */
    protected $threemaMsg;

    /**
     * @var array|string
     */
    protected $log;

    /**
     * @var bool
     */
    protected $saveMessage;

    /**
     * @var bool
     */
    protected $debugMode;

    /**
     * @var string name of the processed message
     */
    protected $name = 'message';

    /**
     * @var string name of the secret contained in a message
     */
    protected $nameSecret = 'data';

    /**
     * @var int the type of the request
     */
    protected $pendingRequestType;

    /**
     * @var array filters/conditions applied to the message
     */
    protected $filters;

    /**
     * @var array all pending request messages found for the current message
     */
    protected $pendingRequests;

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
    public function __construct(ThreemaGateway_Handler_Action_Callback $handler,
                                Threema\MsgApi\Helpers\ReceiveMessageResult $receiveResult,
                                Threema\MsgApi\Messages\ThreemaMessage $threemaMsg,
                                &$output,
                                &$saveMessage,
                                $debugMode)
    {
        $this->callback              = $handler;
        $this->log                   = $output;
        $this->receiveResult         = $receiveResult;
        $this->threemaMsg            = $threemaMsg;
        $this->saveMessage           = $saveMessage;
        $this->debugMode             = $debugMode;
    }

    /**
     * Prepare the message handling.
     *
     * Returns "false" if the process should be canceled. Otherwise "true".
     *
     * @throws XenForo_Exception
     * @return bool
     */
    abstract public function prepareProcessing();

    /**
     * Filters the passed data/message.
     *
     * Returns "false" if the process should be canceled. Otherwise "true".
     * The filters have had to be set by [@see addFilter()] before.
     *
     * @return bool
     */
    public function applyFilters()
    {
        // skip check if there are no filters
        if (!$this->filters) {
            return true;
        }

        foreach ($this->filters as $filter) {
            if (!$this->applyFilter($filter['type'], $filter['data'], $filter['fail'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Processes the pending requests, i.e. iterates all confirm requests and handles
     * them.
     *
     * Returns "false" if the process should be canceled. Otherwise "true".
     *
     * @param  array             $processOptions options, which are passed to {@link processConfirmRequest()}.
     * @throws XenForo_Exception
     *
     * @return bool
     */
    public function processPending(array $processOptions = [])
    {
        if (!$this->pendingRequests) {
            $this->preProcessPending();
        }
        if (!$this->pendingRequests) {
            if (isset($processOptions['requirePendingRequests']) && $processOptions['requirePendingRequests']) {
                throw new XenForo_Exception('preProcessPending() could not get any pending request data.');
            }
            return false;
        }

        // handle all requests
        /** @var bool $success whether the request has been successfully processed */
        $success = false;

        foreach ($this->pendingRequests as $confirmRequest) {
            // now confirm request
            if (!$this->processConfirmRequest($confirmRequest, $processOptions)) {
                // in case of error, just skip the message
                continue;
            }

            $success = true;
        }

        if (!$this->postProcessPending($success)) {
            return false;
        }

        return $success;
    }

    /**
     * Verifies & saves data for one confirm request.
     *
     * Returns "false" if the process should be canceled. Otherwise "true".
     * childs should call the parent here as the things done in this class are
     * essential!
     *
     * @throws XenForo_Exception
     * @return bool
     */
    protected function processConfirmRequest($confirmRequest)
    {
        // first verify send time
        if ($this->messageIsExpired($confirmRequest)) {
            continue;
        }

        // other processing should be done by child

        return true;
    }

    /**
     * Sets the name of the processed message.
     *
     * @param string $name       what message this is, e.g.: XY message
     * @param string $nameSecret what secrets etc. conmtains the message: XY code
     */
    final public function setMessageTypeName($name, $nameSecret)
    {
        $this->name       = $name;
        $this->nameSecret = $nameSecret;
    }

    /**
     * Sets the type of the request.
     *
     * Use one of the constants in
     * ThreemaGateway_Model_TfaPendingMessagesConfirmation::PENDING_*
     *
     * @param int $pendingRequestType
     */
    final public function setPrendingRequestType($pendingRequestType)
    {
        $this->pendingRequestType = $pendingRequestType;
    }

    /**
     * Returns the log and save message data you passed to this class when initiating.
     *
     * This should be called at least once at the end as this is the only way
     * to update the log and save message values.
     *
     * @param array|string $output      [$logType, $debugLog, $publicLog]
     * @param bool         $saveMessage
     */
    final public function getReferencedData(&$output, &$saveMessage)
    {
        $output      = $this->log;
        $saveMessage = $this->saveMessage;
    }

    /**
     * Adds a filter, which is applied to the message (data) when
     * {@see applyFilter()} is called.
     *
     * @param int   $filterType  please use the constants FILTER_*
     * @param mixed $filterData  any data the filter uses
     * @param bool  $failOnError whether the filter should fail on errors (true)
     *                           or silently ignore them (false)
     */
    public function addFilter($filterType, $filterData, $failOnError = true)
    {
        $this->filters[] = [
            'type' => $filterType,
            'data' => $filterData,
            'fail' => $failOnError
        ];
    }

    /**
     * Filters the passed data/message.
     *
     * Returns "false" if the process should be canceled. Otherwise "true".
     *
     * @param int   $filterType  please use the constants FILTER_*
     * @param mixed $filterData  any data the filter uses
     * @param bool  $failOnError whether the filter should fail on errors (true)
     *                           or silently ignore them (false)
     *
     * @throws XenForo_Exception
     * @return bool
     */
    abstract protected function applyFilter($filterType, $filterData, $failOnError = true);

    /**
     * Analyses/filters/validates the existing old provider data e.g. to
     * compare it with the new data to set.
     *
     * Returns "false" if the process should be canceled. Otherwise "true".
     *
     * @param array $confirmRequest  the confirm request
     * @param array $oldProviderData old data read
     * @param array $setData         new data to set
     * @param array $processOptions  custom options (optional)
     *
     * @throws XenForo_Exception
     * @return bool
     */
    protected function preSaveData(array $confirmRequest, array &$oldProviderData, array &$setData, array $processOptions = [])
    {
        return true;
    }

    /**
     * Handles the already merged provider data.
     *
     * Returns "false" if the process should be canceled. Otherwise "true".
     *
     * @param array $confirmRequest the confirm request
     * @param array $providerData   merged provider data
     * @param array $processOptions custom options (optional)
     *
     * @throws XenForo_Exception
     * @return bool
     */
    protected function preSaveDataMerged(array $confirmRequest, array &$providerData, array $processOptions = [])
    {
        return true;
    }

    /**
     * Does some stuff with the data after it has been saved.
     *
     * Returns "false" if the process should be canceled. Otherwise "true".
     *
     * @param array $confirmRequest the confirm request
     * @param array $providerData   old data read
     * @param array $processOptions custom options (optional)
     *
     * @throws XenForo_Exception
     * @return bool
     */
    protected function postSaveData(array $confirmRequest, array &$providerData, array $processOptions = [])
    {
        return true;
    }

    /**
     * Does stuff needed to be done before processing the actual requests.
     *
     * Returns "false" if the process should be canceled. Otherwise "true".
     * Childs should call the parent here as the things done in this class are
     * essential!
     *
     * @throws XenForo_Exception
     * @return bool
     */
    protected function preProcessPending()
    {
        // check whether message has already been saved to prevent replay attacks
        $this->callback->assertNoReplayAttack($this->receiveResult->getMessageId());

        /** @var array|false $this->pendingRequests all pending requests (or false if there are none) */
        if (!$this->pendingRequests = $this->getPendingRequests()) {
            return false;
        }
    }

    /**
     * Does stuff, which needs to be done after processing the requests.
     *
     * Returns "false" if the process should be canceled. Otherwise "true".
     * Childs should call the parent here as the things done in this class are
     * essential!
     *
     * @param  bool              $success whether the data was processed successfully
     * @throws XenForo_Exception
     * @return bool
     */
    protected function postProcessPending($success)
    {
        if ($success) {
            // do not save message as it already has been processed
            $this->saveMessage = false;
        }

        return true;
    }

    /**
     * Returns the pending messages for a given.
     *
     * @return array|false
     */
    protected function getPendingRequests()
    {
        /** @var ThreemaGateway_Model_TfaPendingMessagesConfirmation $pendingRequestsModel */
        $pendingRequestsModel = $this->getModelFromCache('ThreemaGateway_Model_TfaPendingMessagesConfirmation');

        /** @var array|null $pendingRequests all pending requests if there are some */
        $pendingRequests = $pendingRequestsModel->getPending(
            $this->callback->getRequest('from'),
            null,
            $this->pendingRequestType
        );

        if (!$pendingRequests) {
            $this->log('No confirmation requests registered. Abort.');
            return false;
        }

        return $pendingRequests;
    }

    /**
     * Checks whether a message is expired.
     *
     * This uses the date given by the Threema Gateway server (this is the
     * send date) to verify that the message is not expired.
     * Thus the current date is not used for this comparison as this should
     * be done in the 2FA provider directly when verifying the data
     * (verifyFromInput).
     *
     * @param  array             $confirmRequest the confirmation message request
     * @throws XenForo_Exception
     *
     * @return bool
     */
    protected function messageIsExpired(array $confirmRequest)
    {
        if ($this->callback->getRequest('date') > $confirmRequest['expiry_date']) {
            $this->log(
                'Message is too old.',
                'Message is too old, already expired. Maximum: ' . date('Y-m-d H:i:s', $confirmRequest['expiry_date'])
            );
            return true;
        }

        return false;
    }

    /**
     * Saves data for a confirm request (as the provider data of the 2FA method).
     *
     * @param  array             $confirmRequest the confirmation message request
     * @param  array             $setData        an array of the data to set
     * @param  array             $processOptions custom options (optional)
     * @throws XenForo_Exception
     */
    protected function setDataForRequest(
        array $confirmRequest,
        array $setData,
        array $processOptions = []
    ) {
        /** @var array $providerData provider data of session */
        $providerData = [];

        $this->log('', 'Request #' .
            $confirmRequest['request_id'] . ' from ' .
            $confirmRequest['provider_id'] . ' for user ' .
            $confirmRequest['user_id'] . ' for session ' .
            $confirmRequest['session_id']);

        // clear potentially old session data
        $this->session = null;

        try {
            $providerData = $this->getProviderDataBySession($confirmRequest);

            $this->log(
                '',
                'Got provider data from session.'
            );
        } catch (Exception $e) {
            $this->log(
                '',
                $e->getMessage() . ' Try 2FA model.'
            );

            // second try via model
            try {
                $providerData = $this->getProviderDataByModel($confirmRequest);

                $this->log(
                    '',
                    'Got provider data from user model.'
                );
            } catch (Exception $e) {
                $this->log(
                    'Could not get provider data.',
                    $e->getMessage() . ' Abort.'
                );

                // re-throw exception
                throw $e;
            }
        }

        if (!$this->preSaveData($confirmRequest, $providerData, $setData, $processOptions)) {
            throw new Exception('preSaveData() returned an error and prevented data saving.');
        }

        // merge the data with the original provider data
        $providerData = array_merge($providerData, $setData);

        if (!$this->preSaveDataMerged($confirmRequest, $providerData, $processOptions)) {
            throw new Exception('preSaveDataMerged() returned an error and prevented data saving.');
        }

        // and save the data
        try {
            $this->saveProviderData($providerData, $confirmRequest);
        } catch (Exception $e) {
            $this->log('Could not save provider data.', $e->getMessage());

            // re-throw exception
            throw $e;
        }

        $this->log($this->nameSecret . ' saved.', 'Saved ' . $this->nameSecret . ' for request #' .
            $confirmRequest['request_id'] . ' from ' .
            $confirmRequest['provider_id'] . ' for user ' .
            $confirmRequest['user_id'] . ' for session ' .
            $confirmRequest['session_id']);

        if (!$this->postSaveData($confirmRequest, $providerData, $processOptions)) {
            throw new Exception('postSaveData() returned an error.');
        }
    }

    /**
     * Fetches and returns the provider data using the session.
     *
     * @param  array             $confirmRequest the confirmation message request
     * @throws XenForo_Exception
     * @return array
     */
    protected function getProviderDataBySession(array $confirmRequest)
    {
        /** @var XenForo_Session $session */
        $session = $this->getSession();
        $session->threemagwSetupRaw($confirmRequest['session_id'], false);

        /** @var string $sessionKey session key identifying */
        $sessionKey = 'tfaData_' . $confirmRequest['provider_id'];
        /** @var array $providerData provider data of session */
        $providerData = $session->get($sessionKey);

        if (empty($providerData)) {
            throw new XenForo_Exception('Could not get provider data from session using key ' . $sessionKey . '.');
        }

        $this->dataFetchMode = 'session';
        return $providerData;
    }

    /**
     * Fetches and returns the provider data.
     *
     * @param  array             $confirmRequest the confirmation message request
     * @throws XenForo_Exception
     * @return array
     */
    protected function getProviderDataByModel(array $confirmRequest)
    {
        /** @var XenForo_Model_Tfa $tfaModel */
        $tfaModel = $this->getModelFromCache('XenForo_Model_Tfa');

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

        $this->dataFetchMode = 'tfa_model';
        return $providerData;
    }

    /**
     * Gets model from cache or initializes a new model if needed.
     *
     * @param array $newProviderData provider data to save
     * @param array $confirmRequest  the confirmation message request
     *
     * @throws XenForo_Exception
     */
    protected function saveProviderData(array $newProviderData, array $confirmRequest)
    {
        switch ($this->dataFetchMode) {
            case 'session':
                /** @var string $sessionKey session key identifying */
                $sessionKey = 'tfaData_' . $confirmRequest['provider_id'];

                /** @var XenForo_Session $session */
                $session = $this->getSession();

                $session->set($sessionKey, $newProviderData);
                $session->save();
                break;

            case 'tfa_model':
                /** @var XenForo_Model_Tfa $tfaModel */
                $tfaModel = $this->getModelFromCache('XenForo_Model_Tfa');
                $tfaModel->updateUserProvider($confirmRequest['user_id'], $confirmRequest['provider_id'], $newProviderData, false);
                break;

            default:
                // if all fails, we can only throw an exception
                throw new XenForo_Exception('Invalid provider data fetch method: ' . $this->dataFetchMode);
        }
    }

    /**
     * Gets model from cache or initializes a new model if needed.
     *
     * @param string $class Name of class to load
     *
     * @return XenForo_Model
     */
    final protected function getModelFromCache($class)
    {
        if (!isset($this->modelCache[$class])) {
            $this->modelCache[$class] = XenForo_Model::create($class);
        }

        return $this->modelCache[$class];
    }

    /**
     * Returns the XenForo session.
     *
     * @return XenForo_Session
     */
    final protected function getSession()
    {
        if (!$this->session) {
            $class         = XenForo_Application::resolveDynamicClass('XenForo_Session');
            $this->session = new $class;
        }

        return $this->session;
    }

    /**
     * Adds some data to the log.
     *
     * @param  string          $logDetailed
     * @param  string|null     $logGeneral
     * @return XenForo_Session
     */
    final protected function log($logDetailed, $logGeneral = null)
    {
        return $this->callback->addLog($this->log, $logDetailed, $logGeneral);
    }

    /**
     * Returns the user array.
     *
     * @param  int   $userId
     * @return array
     */
    final protected function getUserData($userId)
    {
        if (!isset($this->user[$userId])) {
            /** @var XenForo_Model_User $userModel */
            $userModel = $this->getModelFromCache('XenForo_Model_User');
            /** @var array $user */
            $user = $userModel->getFullUserById($userId);
            if (!$user) {
                throw new XenForo_Exception('Could not get user data data.');
            }

            $this->user[$userId] = $user;
        }

        return $this->user[$userId];
    }
}

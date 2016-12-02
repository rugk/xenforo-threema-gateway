<?php
/**
 * Two factor authentication abstract provider for Threema Gateway.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

/**
 * Threema Gateway Code for two step authentication (TFA/2FA).
 */
abstract class ThreemaGateway_Tfa_AbstractProvider extends XenForo_Tfa_AbstractProvider
{
    /**
     * Variable, which will be filled with object of the Gateway Permissions class.
     *
     * @var ThreemaGateway_Handler_Permissions
     */
    protected $GatewayPermissions;

    /**
     * Variable, which will be filled with object of Gateway Settings later.
     *
     * @var ThreemaGateway_Handler_Settings
     */
    protected $GatewaySettings;

    /**
     * Variable, which will be filled with object of Gateway Handler later.
     *
     * It is private as {@link getSdk()} should be used. This makes sure the SDK
     * is only initialized when it is really needed.
     *
     * @var ThreemaGateway_Handler_PhpSdk
     */
    private $GatewaySdk = null;

    /**
     * Variable, which will be filled with object of Gateway Handler for server actions later.
     *
     * @var ThreemaGateway_Handler_Action_GatewayServer
     */
    protected $GatewayServer;

    /**
     * Variable, which will be filled with object of Gateway Handler for sending actions later.
     *
     * @var ThreemaGateway_Handler_Action_Sender
     */
    protected $GatewaySender;

    /**
     * Create provider.
     *
     * @param string $id Provider id
     */
    public function __construct($id)
    {
        parent::__construct($id);
        $this->GatewayPermissions = ThreemaGateway_Handler_Permissions::getInstance();
        $this->GatewaySettings    = new ThreemaGateway_Handler_Settings;
        $this->GatewayServer      = new ThreemaGateway_Handler_Action_GatewayServer;
        $this->GatewaySender      = new ThreemaGateway_Handler_Action_Sender;
    }

    /**
     * Called when activated. Returns inital data of 2FA methode.
     *
     * @param  array $user
     * @param  array $setupData
     * @return array
     */
    public function generateInitialData(array $user, array $setupData)
    {
        $this->GatewayPermissions->setUserId($user);

        return $setupData;
    }

    /**
     * Called when trying to verify user. Sends Threema message.
     *
     * @param  string $context
     * @param  array  $user
     * @param  string $ip
     * @param  array  $providerData
     * @return array
     */
    public function triggerVerification($context, array $user, $ip, array &$providerData)
    {
        $this->GatewayPermissions->setUserId($user);

        return [];
    }

    /**
     * Called when trying to verify user. Shows code input and such things.
     *
     * @param  XenForo_View $view
     * @param  string       $context
     * @param  array        $user
     * @param  array        $providerData
     * @param  array        $triggerData
     * @return string       HTML code
     */
    public function renderVerification(XenForo_View $view, $context, array $user,
                                        array $providerData, array $triggerData)
    {
        $this->GatewayPermissions->setUserId($user);
    }

    /**
     * Called when trying to verify user. Checks whether a given code is valid.
     *
     * @param string $context
     * @param array  $input
     * @param array  $user
     * @param array  $providerData
     *
     * @return bool
     */
    public function verifyFromInput($context, XenForo_Input $input, array $user, array &$providerData)
    {
        $this->GatewayPermissions->setUserId($user);
    }

    /**
     * Verifies the Treema ID formally after it was entered/changed.
     *
     * @param XenForo_Input $input
     * @param array         $user
     * @param array         $error
     *
     * @return array
     */
    public function verifySetupFromInput(XenForo_Input $input, array $user, &$error)
    {
        $this->GatewayPermissions->setUserId($user);
    }

    /**
     * Handles settings of user.
     *
     * @param XenForo_Controller $controller
     * @param array              $user
     * @param array              $providerData
     *
     * @return null|ThreemaGateway_ViewPublic_TfaManage
     */
    public function handleManage(XenForo_Controller $controller, array $user, array $providerData)
    {
        $this->GatewayPermissions->setUserId($user);
    }

    /**
     * Called when trying to verify user. Checks whether a user meets the
     * requirements.
     *
     * @param array  $user
     * @param object $error
     *
     * @return bool
     */
    public function meetsRequirements(array $user, &$error)
    {
        return true;
    }

    /**
     * Called when verifying displaying the choose 2FA mode.
     *
     * @param array  $user
     * @param object $error
     *
     * @return bool
     */
    public function canEnable()
    {
        // check neccessary permissions
        return $this->GatewaySettings->isReady() && $this->GatewayPermissions->hasPermission('tfa');
    }

    /**
     * Saves new provider options to database.
     *
     * @param array $user
     * @param array $options
     */
    protected function saveProviderOptions($user, array $options)
    {
        /** @var XenForo_Model_Tfa $tfaModel */
        $tfaModel = XenForo_Model::create('XenForo_Model_Tfa');
        $tfaModel->enableUserTfaProvider($user['user_id'], $this->_providerId, $options);
    }

    /**
     * Sends a message to a user and chooses automatically whether E2E mode can
     * be used.
     *
     * @param array $receiverId The Threema ID to who
     * @param array $xenPhrase  The message as a phrase, which should be sent
     */
    final protected function sendMessage($receiverId, XenForo_Phrase $xenPhrase)
    {
        // parse message
        $messageText = $xenPhrase->render();
        $messageText = ThreemaGateway_Handler_Emoji::parseUnicode($messageText);

        // send message
        return $this->GatewaySender->sendAuto($receiverId, $messageText);
    }

    /**
     * Generates a random numeric string consisting of digits.
     *
     * @param  int    $length The length of the string (default: 6)
     * @return string
     */
    final protected function generateRandomCode($length = 6)
    {
        /* @var XenForo_Options */
        $options = XenForo_Application::getOptions();
        /* @var string */
        $code = '';

        if ($options->threema_gateway_tfa_useimprovedsrng) {
            try {
                //use own Sodium method
                /* @var ThreemaGateway_Handler_Libsodium */
                $sodiumHelper = new ThreemaGateway_Handler_Libsodium;
                $code         = $sodiumHelper->getRandomNumeric($length);
            } catch (Exception $e) {
                // ignore errors
            }
        }

        if (!$code) {
            //use XenForo method as a fallback
            $random = XenForo_Application::generateRandomString(4, true);

            $code = (
                ((ord($random[0]) & 0x7f) << 24) |
                ((ord($random[1]) & 0xff) << 16) |
                ((ord($random[2]) & 0xff) << 8) |
                (ord($random[3]) & 0xff)
                    ) % pow(10, $length);
            $code = str_pad($code, $length, '0', STR_PAD_LEFT);
        }

        return $code;
    }

    /**
     * Gets the default Threema ID using different sources.
     *
     * @param  array        $user
     * @return string|false
     */
    final protected function getDefaultThreemaId(array $user)
    {
        $options = XenForo_Application::getOptions();
        /** @var string $threemaId */
        $threemaId = '';

        if (array_key_exists('threemaid', $user['customFields']) &&
            $user['customFields']['threemaid'] != '') {

            //use custom user field
            $threemaId = $user['customFields']['threemaid'];
        }
        if ($threemaId == '' &&
            $options->threema_gateway_tfa_autolookupmail &&
            $user['user_state'] == 'valid') {

            //lookup mail
            try {
                $threemaId = $this->GatewaySdkServer->lookupMail($user['email']);
            } catch (Exception $e) {
                //ignore failure
            }
        }
        if ($threemaId == '' &&
            $options->threema_gateway_tfa_autolookupphone && //verify ACP permission
            $options->threema_gateway_tfa_autolookupphone['enabled'] &&
            $options->threema_gateway_tfa_autolookupphone['userfield'] && //verify ACP setup
            array_key_exists($options->threema_gateway_tfa_autolookupphone['userfield'], $user['customFields']) && //verify user field
            $user['customFields'][$options->threema_gateway_tfa_autolookupphone['userfield']] != '') {

            //lookup phone number
            try {
                $threemaId = $this->GatewaySdkServer->lookupPhone($user['customFields'][$options->threema_gateway_tfa_autolookupphone['userfield']]);
            } catch (Exception $e) {
                //ignore failure
            }
        }

        return $threemaId;
    }

    /**
     * Register a request for a new pending confirmation message.
     *
     * @param array $providerData
     * @param int   $pendingType  What type of message request this is.
     *                            You should use one of the PENDING_* constants
     *                            in the Model (ThreemaGateway_Model_TfaPendingMessagesConfirmation).
     * @param array $user
     * @param string|int $extraData    Any extra data you want to save in the database.
     *
     * @return bool
     */
    final protected function registerPendingConfirmationMessage(array $providerData, $pendingType, array $user, $extraData = null)
    {
        /** @var ThreemaGateway_Model_TfaPendingMessagesConfirmation $model */
        $model = XenForo_DataWriter::create('ThreemaGateway_Model_TfaPendingMessagesConfirmation');
        /** @var ThreemaGateway_DataWriter_TfaPendingMessagesConfirmation $dataWriter */
        $dataWriter = XenForo_DataWriter::create('ThreemaGateway_DataWriter_TfaPendingMessagesConfirmation');


        // check whether the same request is already issued, if so overwrite it
        if ($model->getPending($providerData['threemaid'], $this->_providerId, $pendingType)) {
            $dataWriter->setExistingData([
                ThreemaGateway_Model_TfaPendingMessagesConfirmation::DbTable => [
                    'threema_id' => $providerData['threemaid'],
                    'provider_id' => $this->_providerId,
                    'pending_type' => $pendingType
                ]
            ]);
        }

        $dataWriter->set('threema_id', $providerData['threemaid']);
        $dataWriter->set('provider_id', $this->_providerId);
        $dataWriter->set('pending_type', $pendingType);

        $dataWriter->set('user_id', $user['user_id']);
        $dataWriter->set('session_id', XenForo_Application::getSession()->getSessionId());

        if ($extraData) {
            $dataWriter->set('extra_data', $extraData);
        }
        $dataWriter->set('expiry_date', $providerData['codeGenerated'] + $providerData['validationTime']);

        return $dataWriter->save();
    }

    /**
     * Register a request for a new pending confirmation message.
     *
     * @param array $providerData
     * @param int   $pendingType  What type of message request this is.
     *                            You should use one of the PENDING_* constants
     *                            in the Model (ThreemaGateway_Model_TfaPendingMessagesConfirmation).
     *
     * @return bool
     */
    final protected function unregisterPendingConfirmationMessage(array $providerData, $pendingType)
    {
        /** @var ThreemaGateway_DataWriter_TfaPendingMessagesConfirmation $dataWriter */
        $dataWriter = XenForo_DataWriter::create('ThreemaGateway_DataWriter_TfaPendingMessagesConfirmation');

        $dataWriter->setExistingData([
            ThreemaGateway_Model_TfaPendingMessagesConfirmation::DbTable => [
                'threema_id' => $providerData['threemaid'],
                $this->_providerId,
                'pending_type' => $pendingType
            ]
        ]);

        return $dataWriter->delete();
    }

    /**
     * Verifies whether the new code is valid considering timing information
     * about the current code.
     *
     * @param  array $providerData
     * @return bool
     */
    final protected function verifyCodeTiming(array $providerData)
    {
        if (empty($providerData['code']) || empty($providerData['codeGenerated'])) {
            return false;
        }

        if ((XenForo_Application::$time - $providerData['codeGenerated']) > $providerData['validationTime']) {
            return false;
        }

        return true;
    }

    /**
     * Verifies whether the new code is valid by comparing it with the previous
     * code.
     *
     * @param  array  $providerData
     * @param  string $newCode      the new code, which is currently checked/verified
     * @return bool
     */
    final protected function verifyCodeReplay(array $providerData, $newCode)
    {
        if (!empty($providerData['lastCode']) && $this->stringCompare($providerData['lastCode'], $newCode)) {
            // prevent replay attacks: once the code has been used, don't allow it to be used in the slice again
            if (!empty($providerData['lastCodeTime']) && (XenForo_Application::$time - $providerData['lastCodeTime']) < $providerData['validationTime']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Updates the data used by {@see verifyCodeReplay()} to prevent replay attacks.
     *
     * @param  array  $providerData
     * @param  string|int  $code    The currently processed (& verified) code
     * @return bool
     */
    final protected function updateReplayCheckData(array &$providerData, $code)
    {
        // save current code for later replay attack checks
        $providerData['lastCode']     = $providerData['receivedCode'];
        $providerData['lastCodeTime'] = XenForo_Application::$time;
        unset($providerData['code']);
        unset($providerData['codeGenerated']);

        return true;
    }

    /**
     * Parse a given number of minutes to a human-readble format.
     *
     * @param  int    $minutes
     * @return string
     */
    final protected function parseValidationTime($minutes)
    {
        /** @var string $vadilityTimeDisplay */
        $vadilityTimeDisplay = floor($minutes / 60);
        if ($vadilityTimeDisplay <= 1) {
            $vadilityTimeDisplay .= ' ' . new XenForo_Phrase('threemagw_minute');
        } else {
            $vadilityTimeDisplay .= ' ' . new XenForo_Phrase('threemagw_minutes');
        }

        return $vadilityTimeDisplay;
    }

    /**
     * Checks whether a string is the same (returns true) or not (returns false).
     *
     * This should be used for security-sensitive things as it checks the
     * strings constant-time.
     *
     * @param  string $string1
     * @param  string $string2
     * @return bool
     */
    final protected function stringCompare($string1, $string2)
    {
        return $this->getSdk()->getCryptTool()->stringCompare($string1, $string2);
    }

    /**
     * Returns the PHP SDK object.
     *
     * @param ThreemaGateway_Handler_PhpSdk
     */
    final protected function getSdk()
    {
        if ($this->GatewaySdk === null) {
            $this->GatewaySdk = ThreemaGateway_Handler_PhpSdk::getInstance($this->GatewaySettings);
        }

        return $this->GatewaySdk;
    }
}

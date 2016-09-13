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
     * Variable, which will be filled with object of Gateway Handler later.
     *
     * @var ThreemaGateway_Handler
     */
    protected $GatewayHandler;

    /**
     * Variable, which will be filled with object of Gateway Handler for server actions later.
     *
     * @var ThreemaGateway_Handler_GatewayServer
     */
    protected $GatewayHandlerServer;

    /**
     * Create provider.
     *
     * @param string $id Provider id
     */
    public function __construct($id)
    {
        parent::__construct($id);
        $this->GatewayHandler = ThreemaGateway_Handler::getInstance();
        $this->GatewayHandlerServer = new ThreemaGateway_Handler_GatewayServer;
    }

    /**
     * Called when activated. Returns inital data of 2FA methode.
     *
     * @param array $user
     * @param array $setupData
     */
    public function generateInitialData(array $user, array $setupData)
    {
        $this->GatewayHandler->setUserId($user);
    }

    /**
     * Called when trying to verify user. Sends Threema message.
     *
     * @param string $context
     * @param array  $user
     * @param string $ip
     * @param array  $providerData
     */
    public function triggerVerification($context, array $user, $ip, array &$providerData)
    {
        $this->GatewayHandler->setUserId($user);
    }

    /**
     * Called when trying to verify user. Shows code input and such things.
     *
     * @param XenForo_View $view
     * @param string       $context
     * @param array        $user
     * @param array        $providerData
     * @param array        $triggerData
     */
    public function renderVerification(XenForo_View $view, $context, array $user,
                                        array $providerData, array $triggerData)
    {
        $this->GatewayHandler->setUserId($user);
    }

    /**
     * Called when trying to verify user. Checks whether given code is valid.
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
        $this->GatewayHandler->setUserId($user);
    }

    /**
     * Verifies the Treema ID formally after it was entered/changed.
     *
     * @param XenForo_Input $input
     * @param array         $user
     * @param array         $error
     *
     * @return bool
     */
    public function verifySetupFromInput(XenForo_Input $input, array $user, &$error)
    {
        $this->GatewayHandler->setUserId($user);
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
        $this->GatewayHandler->setUserId($user);
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
        // get neccessary permissions
        $permission = $this->GatewayHandler->hasPermission('use');
        $permission &= $this->GatewayHandler->hasPermission('tfa');

        return $this->GatewayHandler->isReady() && $permission;
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
    protected function sendMessage($receiverId, XenForo_Phrase $xenPhrase)
    {
        // parse message
        $messageText = $xenPhrase->render();
        $messageText = ThreemaGateway_Handler_Emoji::parseUnicode($messageText);

        // send message
        if ($this->GatewayHandler->isEndToEnd()) {
            return $this->GatewayHandler->sendE2EText($receiverId, $messageText);
        } else {
            return $this->GatewayHandler->sendSimple($receiverId, $messageText);
        }
    }

    /**
     * Generates a random numeric string.
     *
     * @param  int    $length The length of the string (default: 6)
     * @return string
     */
    protected function generateRandomString($length = 6)
    {
        $options = XenForo_Application::getOptions();

        if ($options->threema_gateway_tfa_usesodiumran) {
            //use own Sodium method
            /* @var ThreemaGateway_Handler_Libsodium */
            $sodiumHelper = new ThreemaGateway_Handler_Libsodium;
            $code         = $sodiumHelper->getRandomNumeric($length);
        } else {
            //use XenForo method
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
    protected function getDefaultThreemaId(array $user)
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
                $threemaId = $this->GatewayHandlerServer->lookupMail($user['email']);
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
                $threemaId = $this->GatewayHandlerServer->lookupPhone($user['customFields'][$options->threema_gateway_tfa_autolookupphone['userfield']]);
            } catch (Exception $e) {
                //ignore failure
            }
        }

        return $threemaId;
    }
}

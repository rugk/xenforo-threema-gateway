<?php
/**
 * Two factor authentication provider for Threema Gateway which sends a
 * confirmation message.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

/**
 * TFA where the user acknowledges a message sent my the server.
 */
class ThreemaGateway_Tfa_Fast extends ThreemaGateway_Tfa_AbstractProvider
{
    /**
     * Called when verifying displaying the choose 2FA mode.
     *
     * @return bool
     */
    public function canEnable()
    {
        if (!parent::canEnable()) {
            return false;
        }

        // check whether it is activated in the settings
        /** @var XenForo_Options $options */
        $options = XenForo_Application::getOptions();
        if (!$options->threema_gateway_tfa_fast) {
            return false;
        }

        // check specific permissions
        if (!$this->gatewayPermissions->hasPermission('send') ||
            !$this->gatewayPermissions->hasPermission('receive') ||
            !$this->gatewayPermissions->hasPermission('fetch')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Called when trying to verify user. Creates code and registers callback
     * request.
     *
     * @param  string $context
     * @param  array  $user
     * @param  string $ip
     * @param  array  $providerData
     * @return array
     */
    public function triggerVerification($context, array $user, $ip, array &$providerData)
    {
        parent::triggerVerification($context, $user, $ip, $providerData);

        if (!$providerData) {
            return [];
        }

        /** @var XenForo_Options $options */
        $options = XenForo_Application::getOptions();

        //message is only valid for some time
        if ($context == 'setup') {
            $providerData['validationTime'] = $options->threema_gateway_tfa_fast_validation_setup * 60; //default: 10 minutes
        } else {
            $providerData['validationTime'] = $options->threema_gateway_tfa_fast_validation * 60; //default: 3 minutes
        }

        // send message
        /** @var string $phrase name of XenForo phrase to use */
        $phrase = 'tfa_threemagw_fast_message';
        if ($providerData['useShortMessage']) {
            $phrase = 'tfa_threemagw_fast_message_short';
        }

        /** @var XenForo_Phrase $message */
        $message = new XenForo_Phrase($phrase, [
            'user' => $user['username'],
            'ip' => $ip,
            'validationTime' => $this->parseValidationTime($providerData['validationTime']),
            'board' => $options->boardTitle,
            'board_url' => $options->boardUrl
        ]);

        /** @var int $messageId  */
        $messageId = $this->sendMessage($providerData['threemaid'], $message);

        // save message ID as code here!
        $providerData['code']          = $messageId;
        $providerData['codeGenerated'] = XenForo_Application::$time;

        // most register message request for Threema callback
        $this->registerPendingConfirmationMessage(
            $providerData,
            ThreemaGateway_Model_TfaPendingMessagesConfirmation::PENDING_REQUEST_DELIVERY_RECEIPT,
            $user,
            $messageId
        );

        return [];
    }

    /**
     * Called when trying to verify user. Shows code, so user can send it via
     * Threema.
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
        parent::renderVerification($view, $context, $user, $providerData, $triggerData);

        $params = [
            'data' => $providerData,
            'trigger' => $triggerData,
            'context' => $context,
            'gatewayid' => $this->gatewaySettings->getId()
        ];
        return $view->createTemplateObject('two_step_threemagw_fast', $params)->render();
    }

    /**
     * Called when trying to verify user. Checks whether the code was received
     * from the Threema Gateway callback.
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
        parent::verifyFromInput($context, $input, $user, $providerData);

        // assure that code has not expired yet
        if (!$this->verifyCodeTiming($providerData)) {
            return false;
        }

        // assure that code has been received at all
        if (!isset($providerData['receivedCode'])) {
            return false;
        }

        // assure replay attacks
        if (!$this->verifyCodeReplay($providerData, $providerData['receivedCode'])) {
            return false;
        }

        // assure that the code is the same as required
        if (!$this->stringCompare($providerData['code'], $providerData['receivedCode'])) {
            return false;
        }

        // assure that the current receipt message is *not* a decline message
        if ($providerData['receivedDeliveryReceipt'] === 4) {
            // take more drastic steps if it is
            $this->handleMessageDecline($providerData);
            return false; // and fail silently
        }

        // assure that the receipt message is a confirmation receipt
        // or has at least been a receipe before
        if ($providerData['receivedDeliveryReceipt'] !== 3 &&
            $providerData['receivedDeliveryReceiptLargest'] !== 3
        ) {
            return false;
        }

        $this->updateReplayCheckData($providerData, $providerData['receivedCode']);

        // unregister confirmation
        $this->unregisterPendingConfirmationMessage(
            $providerData,
            ThreemaGateway_Model_TfaPendingMessagesConfirmation::PENDING_REQUEST_DELIVERY_RECEIPT
        );

        return true;
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
        /** @var array $providerData */
        $providerData = parent::verifySetupFromInput($input, $user, $error);

        //add other options to provider data
        $providerData['useShortMessage']  = $input->filterSingle('useShortMessage', XenForo_Input::BOOLEAN);

        return $providerData;
    }

    /**
     * Called before the setup verification is shown.
     *
     * @param array $providerData
     * @param array $triggerData
     *
     * @return bool
     */
    protected function initiateSetupData(array &$providerData, array &$triggerData)
    {
        return true;
    }

    /**
     * Generates the default provider options at setup time before it is
     * displayed to the user.
     *
     * @return array
     */
    protected function generateDefaultData()
    {
        return [
            'useShortMessage' => false,
        ];
    }

    /**
     * Adjust the view aparams, e.g. add special params needed by your
     * template.
     *
     * @param array  $viewParams
     * @param string $context
     *
     * @return array
     */
    protected function adjustViewParams(array $viewParams, $context)
    {
        /** @var XenForo_Options $xenOptions */
        $xenOptions = XenForo_Application::getOptions();

        $viewParams += [
            'https' => XenForo_Application::$secure,
            'showqrcode' => $xenOptions->threema_gateway_tfa_fast_show_qr_code,
            'gatewayid' => $this->gatewaySettings->getId()
        ];

        return $viewParams;
    }

    /**
     * Handles the actions when a user declines a received message.
     *
     * It can ...
     *
     * @param array $providerData
     */
    protected function handleMessageDecline(array $providerData)
    {
        // possibly ban user, etc.
        // (should be customizable)
    }
}

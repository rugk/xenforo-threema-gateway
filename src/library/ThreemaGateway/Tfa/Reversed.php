<?php
/**
 * Two factor authentication provider for Threema Gateway which waites for a
 * secret/code transfered via Threema.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

/**
 * TFA where the user sends a login secret via Threema.
 */
class ThreemaGateway_Tfa_Reversed extends ThreemaGateway_Tfa_AbstractProvider
{
    /**
     * Return a description of the 2FA methode.
     */
    public function getDescription()
    {
        /** @var XenForo_Options $options */
        $options = XenForo_Application::getOptions();
        /** @var array $params */
        $params = [
            'board' => $options->boardTitle
        ];

        return new XenForo_Phrase('tfa_' . $this->_providerId . '_desc', $params);
    }

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
        if (!$options->threema_gateway_tfa_reversed) {
            return false;
        }

        // this 2FA mode requires end-to-end encryption
        if (!$this->gatewaySettings->isEndToEnd()) {
            return false;
        }

        // check specific permissions
        if (!$this->gatewayPermissions->hasPermission('receive') ||
            !$this->gatewayPermissions->hasPermission('fetch')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Called when trying to verify user. Creates secret and registers callback
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

        // this 2FA mode requires end-to-end encryption
        if (!$this->gatewaySettings->isEndToEnd()) {
            throw new XenForo_Exception(new XenForo_Phrase('threema_this_action_required_e2e'));
        }

        /** @var XenForo_Options $options */
        $options = XenForo_Application::getOptions();

        /** @var string $secret random 6 digit string */
        $secret = $this->generateRandomSecret();

        $providerData['secret']          = $secret;
        $providerData['secretGenerated'] = XenForo_Application::$time;

        //secret is only valid for some time
        if ($context == 'setup') {
            $providerData['validationTime'] = $options->threema_gateway_tfa_reversed_validation_setup * 60; //default: 10 minutes
        } else {
            $providerData['validationTime'] = $options->threema_gateway_tfa_reversed_validation * 60; //default: 3 minutes
        }

        // most importantly register message request for Threema callback
        $this->registerPendingConfirmationMessage(
            $providerData,
            ThreemaGateway_Model_TfaPendingMessagesConfirmation::PENDING_REQUEST_CODE,
            $user
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

        $triggerData['secret'] = $providerData['secret'];
        if ($providerData['useNumberSmilies']) {
            $triggerData['secretWithSmiley'] = ThreemaGateway_Helper_Emoji::parseUnicode(
                ThreemaGateway_Helper_Emoji::replaceDigits($triggerData['secret'])
            );
        }

        /** @var XenForo_Options $xenOptions */
        $xenOptions = XenForo_Application::getOptions();

        $params = [
            'data' => $providerData,
            'trigger' => $triggerData,
            'context' => $context,
            'validationTime' => $this->parseTime($providerData['validationTime']),
            'gatewayid' => $this->gatewaySettings->getId(),
            'autoTrigger' => $xenOptions->threema_gateway_tfa_reversed_auto_trigger
        ];
        return $view->createTemplateObject('two_step_threemagw_reversed', $params)->render();
    }

    /**
     * Called when trying to verify user. Checks whether the secret was received
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
        $result = parent::verifyFromInput($context, $input, $user, $providerData);

        // let errors pass through
        if (!$result) {
            return $result;
        }

        // verify that secret has not expired yet
        if (!$this->verifySecretIsInTime($providerData)) {
            return false;
        }

        // check whether secret has been received at all
        if (!isset($providerData['receivedSecret'])) {
            return false;
        }

        // prevent replay attacks
        if (!$this->verifyNoReplayAttack($providerData, $providerData['receivedSecret'])) {
            return false;
        }

        // check whether the secret is the same as required
        if (!$this->stringCompare($providerData['secret'], $providerData['receivedSecret'])) {
            return false;
        }

        $this->updateReplayCheckData($providerData, $providerData['receivedSecret']);

        // unregister confirmation
        $this->unregisterPendingConfirmationMessage(
            $providerData,
            ThreemaGateway_Model_TfaPendingMessagesConfirmation::PENDING_REQUEST_CODE
        );

        $this->resetProviderOptionsForTrigger($context, $providerData);

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

        // let errors pass through
        if (!$providerData) {
            return $providerData;
        }

        //add other options to provider data
        $providerData['useNumberSmilies'] = $input->filterSingle('useNumberSmilies', XenForo_Input::BOOLEAN);

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
            'useNumberSmilies' => true,
        ];
    }

    /**
     * Adjust the view params for managing the 2FA mode, e.g. add special
     * params needed by your template.
     *
     * @param array  $viewParams
     * @param string $context
     * @param array  $user
     *
     * @return array
     */
    protected function adjustViewParams(array $viewParams, $context, array $user)
    {
        /** @var XenForo_Options $xenOptions */
        $xenOptions = XenForo_Application::getOptions();

        $viewParams += [
            'https' => XenForo_Application::$secure,
            'showqrcode' => $xenOptions->threema_gateway_tfa_reversed_show_qr_code,
            'gatewayid' => $this->gatewaySettings->getId()
        ];

        return $viewParams;
    }

    /**
     * Resets the provider options to make sure the current 2FA verification
     * does not affect the next one.
     *
     * @param string $context
     * @param array $providerData
     */
    protected function resetProviderOptionsForTrigger($context, array &$providerData)
    {
        parent::resetProviderOptionsForTrigger($context, $providerData);

        if (isset($providerData['receivedSecret'])) {
            unset($providerData['receivedSecret']);
        }
    }
}

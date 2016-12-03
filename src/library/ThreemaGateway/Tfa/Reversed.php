<?php
/**
 * Two factor authentication provider for Threema Gateway which waites for a
 * code transfered via Threema.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

/**
 * TFA where the user sends a login code via Threema.
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

        // check specific permissions
        if (!$this->gatewayPermissions->hasPermission('receive') ||
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

        /** @var string $code random 6 digit string */
        $code = $this->generateRandomCode();

        $providerData['code']          = $code;
        $providerData['codeGenerated'] = XenForo_Application::$time;

        //code is only valid for some time
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

        $triggerData['code'] = $providerData['code'];
        if ($providerData['useNumberSmilies']) {
            $triggerData['codeSmileys'] = ThreemaGateway_Helper_Emoji::parseUnicode(ThreemaGateway_Helper_Emoji::replaceDigits($triggerData['code']));
        }

        $params = [
            'data' => $providerData,
            'trigger' => $triggerData,
            'context' => $context,
            'validationTime' => $this->parseValidationTime($providerData['validationTime']),
            'gatewayid' => $this->gatewaySettings->getId()
        ];
        return $view->createTemplateObject('two_step_threemagw_reversed', $params)->render();
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

        // verify that code has not expired yet
        if (!$this->verifyCodeTiming($providerData)) {
            return false;
        }

        // check whether code has been received at all
        if (!isset($providerData['receivedCode'])) {
            return false;
        }

        // prevent replay attacks
        if (!$this->verifyCodeReplay($providerData, $providerData['receivedCode'])) {
            return false;
        }

        // check whether the code is the same as required
        if (!$this->stringCompare($providerData['code'], $providerData['receivedCode'])) {
            return false;
        }

        $this->updateReplayCheckData($providerData, $providerData['receivedCode']);

        // unregister confirmation
        $this->unregisterPendingConfirmationMessage(
            $providerData,
            ThreemaGateway_Model_TfaPendingMessagesConfirmation::PENDING_REQUEST_CODE
        );

        return true;
    }

    /**
     * Called when setting up the provider before the setup page is shown.
     *
     * Currently this is not correctly implemented in XenForo.
     * See {@link https://xenforo.com/community/threads/1-5-documentation-for-two-step-authentication.102846/#post-1031047}
     *
     * @param XenForo_Input $input
     * @param array         $user
     * @param array         $error
     *
     * @return string HTML code
     */
    public function renderSetup(XenForo_View $view, array $user)
    {
        // redirected by ThreemaGateway_ControllerPublic_Account->actionTwoStepEnable
        // to handleManage.
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
            'showqrcode' => $xenOptions->threema_gateway_tfa_reversed_show_qr_code,
            'gatewayid' => $this->gatewaySettings->getId()
        ];

        return $viewParams;
    }
}

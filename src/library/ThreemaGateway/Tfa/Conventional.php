<?php
/**
 * Two factor authentication provider for Threema Gateway which sends a code.
 * to the user.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

/**
 * TFA where the user gets a login code. Similar to SMS/email 2FA.
 */
class ThreemaGateway_Tfa_Conventional extends ThreemaGateway_Tfa_AbstractProvider
{
    /**
     * Return a description of the 2FA methode.
     */
    public function getDescription()
    {
        /** @var array $params */
        $params = [];
        if ($this->gatewaySettings->isEndToEnd()) {
            $params['e2e'] = new XenForo_Phrase('threemagw_message_is_sent_e2e');
        } else {
            $params['e2e'] = '';
        }

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
        if (!$options->threema_gateway_tfa_conventional) {
            return false;
        }

        // check specific permissions
        if (!$this->gatewayPermissions->hasPermission('send') ||
            !$this->gatewayPermissions->hasPermission('fetch')
        ) {
            return false;
        }

        return true;
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
            $providerData['validationTime'] = $options->threema_gateway_tfa_conventional_validation_setup * 60; //default: 10 minutes
        } else {
            $providerData['validationTime'] = $options->threema_gateway_tfa_conventional_validation * 60; //default: 3 minutes
        }

        // add options
        if ($providerData['useNumberSmilies']) {
            $code = ThreemaGateway_Helper_Emoji::replaceDigits($code);
        } else {
            // make code a bold text
            $code = '*' . $code . '*';
        }

        /** @var string $phrase name of XenForo phrase to use */
        $phrase = 'tfa_threemagw_conventional_message';
        if ($providerData['useShortMessage']) {
            $phrase = 'tfa_threemagw_conventional_message_short';
        }

        $message = new XenForo_Phrase($phrase, [
            'code' => $code,
            'user' => $user['username'],
            'ip' => $ip,
            'validationTime' => $this->parseValidationTime($providerData['validationTime']),
            'board' => $options->boardTitle,
            'board_url' => $options->boardUrl
        ]);

        $this->sendMessage($providerData['threemaid'], $message);

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
        parent::renderVerification($view, $context, $user, $providerData, $triggerData);

        $params = [
            'data' => $providerData,
            'context' => $context,
        ];

        return $view->createTemplateObject('two_step_threemagw_conventional', $params)->render();
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
        parent::verifyFromInput($context, $input, $user, $providerData);

        // verify that code has not expired yet
        if (!$this->verifyCodeTiming($providerData)) {
            return false;
        }

        /** @var string $code 6 digit string given as parameter */
        $code = $input->filterSingle('code', XenForo_Input::STRING);
        $code = preg_replace('/[^0-9]/', '', $code); //remove all non-numeric characters
        if (!$code) {
            return false;
        }

        // prevent replay attacks
        if (!$this->verifyCodeReplay($providerData, $code)) {
            return false;
        }

        // compare required and given code
        if (!$this->stringCompare($providerData['code'], $code)) {
            return false;
        }

        $this->updateReplayCheckData($providerData, $code);

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
        $providerData['useNumberSmilies'] = $input->filterSingle('useNumberSmilies', XenForo_Input::BOOLEAN);
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
            'useNumberSmilies' => true,
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
        return $viewParams;
    }
}

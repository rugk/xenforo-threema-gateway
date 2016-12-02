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
     * Return the title of the 2FA methode.
     */
    public function getTitle()
    {
        return new XenForo_Phrase('tfa_threemagw_conventional');
    }

    /**
     * Return a description of the 2FA methode.
     */
    public function getDescription()
    {
        /** @var array $params */
        $params = [];
        if ($this->GatewaySettings->isEndToEnd()) {
            $params['e2e'] = new XenForo_Phrase('threemagw_message_is_sent_e2e');
        } else {
            $params['e2e'] = '';
        }

        return new XenForo_Phrase('tfa_threemagw_conventional_desc', $params);
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
        if (!$this->GatewayPermissions->hasPermission('send') ||
            !$this->GatewayPermissions->hasPermission('fetch')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Called when activated. Returns inital data of 2FA methode.
     *
     * @param array $user
     * @param array $setupData
     * @return array
     */
    public function generateInitialData(array $user, array $setupData)
    {
        $setupData = parent::generateInitialData($user, $setupData);

        return $setupData;
    }

    /**
     * Called when trying to verify user. Sends Threema message.
     *
     * @param string $context
     * @param array  $user
     * @param string $ip
     * @param array  $providerData
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
            $code = ThreemaGateway_Handler_Emoji::replaceDigits($code);
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
     * @param XenForo_View $view
     * @param string       $context
     * @param array        $user
     * @param array        $providerData
     * @param array        $triggerData
     * @return string HTML code
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
     * @return bool
     */
    public function canManage()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function requiresSetup()
    {
        // Prevent setup functionality to execute when we are still in step one
        // of the setup.
        $session    = XenForo_Application::getSession();
        /** @var string $sessionKey */
        $sessionKey = 'tfaData_' . $this->_providerId;
        if ($session->get($sessionKey)) {
            return false;
        }
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
        parent::verifySetupFromInput($input, $user, $error);

        /** @var array $providerData */
        $providerData = [];
        /** @var string $threemaid Threema ID given as parameter */
        $threemaid    = $input->filterSingle('threemaid', XenForo_Input::STRING);

        //check Threema ID
        /** @var string $verifyError */
        $verifyError = '';
        if (ThreemaGateway_Handler_Validation::checkThreemaId($threemaid, 'personal', $verifyError)) {
            // correct
            $providerData['threemaid'] = $threemaid;
        } else {
            // incorrect
            $error[] = $verifyError;
            return [];
        }

        //add other options to provider data
        $providerData['useNumberSmilies'] = $input->filterSingle('useNumberSmilies', XenForo_Input::BOOLEAN);
        $providerData['useShortMessage']  = $input->filterSingle('useShortMessage', XenForo_Input::BOOLEAN);

        return $providerData;
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
        parent::handleManage($controller, $user, $providerData);

        /** @var XenForo_Input $input */
        $input   = $controller->getInput();
        /** @var Zend_Controller_Request_Http $request */
        $request = $controller->getRequest();
        /** @var XenForo_Session $session */
        $session = XenForo_Application::getSession();

        /** @var array|null $newProviderData */
        $newProviderData = null;
        /** @var array|null $newTriggerData */
        $newTriggerData  = null;
        /** @var bool $showSetup */
        $showSetup       = false;
        /** @var string $context */
        $context         = 'setup';
        /** @var string $threemaId */
        $threemaId       = '';

        /* Possible values of $context in order of usual appearance
        firstsetup      Input=Threema ID    User enables 2FA provider the first time.
        setupvalidation Input=2FA code      Confirming 2FA in initial setup. (2FA input context: setup)

        setup           Input=Threema ID    UI to change settings of 2FA provider (shows when user clicks on "Manage")
        update          Input=2FA code      Confirming 2FA when settings changed. (2FA input context: setup)

        <not here>      Input=2FA c. only   Login page, where code requested (2FA input context: login)

        The usual template is account_two_step_threemagw_conventional_manage, which includes
        account_two_step_threemagw_conventional every time when a 2FA code is requested. If so
        this "subtemplate" always gets the context "setup".
        Only when logging in this template is included by itself and gets the context "login".
        */

        /* Ways this function can go: Input (filterSingle) --> action --> output ($context)
        Initial setup:
            no $providerData --> set default options & Threema ID --> firstsetup
            step = setup --> show page where user can enter 2FA code --> setupvalidation
            <verification not done in method>

        Manage:
            ... (last else block) --> manage page: show setup --> setup
            manage --> show page where user can enter 2FA code --> update
            confirm --> check 2FA code & use settings if everything is right --> <null>

        Login:
            <not manmaged in this function>
        */

        if ($controller->isConfirmedPost()) {
            $sessionKey = 'tfaData_' . $this->_providerId;

            //setup changed
            if ($input->filterSingle('manage', XenForo_Input::BOOLEAN)) {
                //provider data (settings) changed

                //read and verify options
                $error           = '';
                $newProviderData = $this->verifySetupFromInput($input, $user, $error);
                if (!$newProviderData) {
                    return $controller->responseError($error);
                }

                //check if there is a new ID, which would require revalidation
                if ($newProviderData['threemaid'] == $providerData['threemaid']) {
                    //the same Threema ID - use options instantly
                    $this->saveProviderOptions($user, $newProviderData);
                    return null;
                }

                //validation is required, revalidate this thing...
                $newTriggerData = $this->triggerVerification('setup', $user, $request->getClientIp(false), $newProviderData);

                $session->set($sessionKey, $newProviderData);
                $showSetup = true;
                $context   = 'update';
            } elseif ($input->filterSingle('confirm', XenForo_Input::BOOLEAN)) {
                //confirm setup validation

                //validate new provider data
                $newProviderData = $session->get($sessionKey);
                if (!is_array($newProviderData)) {
                    return null;
                }

                if (!$this->verifyFromInput('setup', $input, $user, $newProviderData)) {
                    return $controller->responseError(new XenForo_Phrase('two_step_verification_value_could_not_be_confirmed'));
                }

                //update provider as everything is okay
                $this->saveProviderOptions($user, $newProviderData);
                $session->remove($sessionKey);

                return null;
            } elseif ($input->filterSingle('step', XenForo_Input::BOOLEAN) == 'setup') {
                //show "real" setup (where you have to enter your validation code)
                $context = 'setupvalidation';

                $newProviderData = $providerData;
                $session->set($sessionKey, $newProviderData);

                $newTriggerData = []; //is not used anyway...
                $showSetup      = true;
            } else {
                throw new XenForo_Exception('Request invalid.');
            }
        } elseif (empty($providerData)) { //no previous settings
            //show first setup page (you can enter your Threema ID and settings)
            $context = 'firstsetup';

            //set default values of options
            $providerData['useNumberSmilies'] = true;
            $providerData['useShortMessage']  = false;

            $threemaId = $this->getDefaultThreemaId($user);
        } else {
            //first manage page ($context = setup)
            $threemaId = $providerData['threemaid'];
        }

        /** @var array $viewParams parameters for XenForo_ControllerResponse_View */
        $viewParams = [
            'provider' => $this,
            'providerId' => $this->_providerId,
            'user' => $user,
            'providerData' => $providerData,
            'newProviderData' => $newProviderData,
            'newTriggerData' => $newTriggerData,
            'showSetup' => $showSetup,
            'context' => $context,
            'threemaId' => $threemaId
        ];
        return $controller->responseView(
            'ThreemaGateway_ViewPublic_TfaManage',
            'account_two_step_threemagw_conventional_manage',
            $viewParams
        );
    }
}

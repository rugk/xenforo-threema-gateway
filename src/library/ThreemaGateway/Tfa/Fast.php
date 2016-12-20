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

        // this 2FA mode requires end-to-end encryption
        if (!$this->gatewaySettings->isEndToEnd()) {
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

        // this 2FA mode requires end-to-end encryption
        if (!$this->gatewaySettings->isEndToEnd()) {
            throw new XenForo_Exception(new XenForo_Phrase('threema_this_action_required_e2e'));
        }

        /** @var XenForo_Options $options */
        $options = XenForo_Application::getOptions();

        //message is only valid for some time
        if ($context == 'setup') {
            $providerData['validationTime'] = $options->threema_gateway_tfa_fast_validation_setup * 60; //default: 10 minutes
        } else {
            $providerData['validationTime'] = $options->threema_gateway_tfa_fast_validation * 60; //default: 3 minutes
        }

        // temporarily save IP, which triggered this
        $providerData['triggerIp'] = $ip;

        // send message
        /** @var string $phrase name of XenForo phrase to use */
        $phrase = 'tfa_threemagw_fast_message';
        if ($context == 'setup') {
            $phrase = 'tfa_threemagw_fast_setup_message';
        }

        /** @var bool $isBlocked true when the user is blocked */
        $isBlocked = false;
        // whether the login is still blocked right now
        if ($this->userIsBlocked($providerData, true)) {
            $isBlocked = true;
            if (
                $this->gatewayPermissions->hasPermission('blockedNotification') &&
                !$providerData['blockedNotification']
            ) {
                // skip message sending
                // This is not recommend as it makes the whole request faster,
                // which means timing attacks can be used to detect that the
                // user is blocked
                return [];
            }

            // silently send a block message noticing the user of the
            // blocking
            $phrase .= '_blocked';
        }

        if (!$isBlocked && $providerData['useShortMessage']) {
            $phrase .= '_short';
        }

        /** @var XenForo_Phrase $message */
        $message = new XenForo_Phrase($phrase, [
            'user' => $user['username'],
            'ip' => $ip,
            'validationTime' => $this->parseTime($providerData['validationTime']),
            'board' => $options->boardTitle,
            'board_url' => $options->boardUrl
        ]);

        /** @var int $messageId */
        $messageId = $this->sendMessage($providerData['threemaid'], $message);

        // save message ID as secret here
        $providerData['secret']          = $messageId;
        $providerData['secretGenerated'] = XenForo_Application::$time;

        // register message request for Threema callback
        $this->registerPendingConfirmationMessage(
            $providerData,
            ThreemaGateway_Model_TfaPendingMessagesConfirmation::PENDING_REQUEST_DELIVERY_RECEIPT,
            $user,
            $messageId
        );

        return [];
    }

    /**
     * Called when trying to verify user. Shows only the request to confirm
     * the message.
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

        /** @var XenForo_Options $xenOptions */
        $xenOptions = XenForo_Application::getOptions();

        $params = [
            'data' => $providerData,
            'trigger' => $triggerData,
            'context' => $context,
            'validationTime' => $this->parseTime($providerData['validationTime']),
            'gatewayid' => $this->gatewaySettings->getId(),
            'autoTrigger' => $xenOptions->threema_gateway_tfa_fast_auto_trigger
        ];
        return $view->createTemplateObject('two_step_threemagw_fast', $params)->render();
    }

    /**
     * Called when trying to verify user. Checks whether the delivery receipt was received
     * from the Threema Gateway callback and acknowledges the message.
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

        // assure that secret has not expired yet
        if (!$this->verifySecretIsInTime($providerData)) {
            return false;
        }

        // assure that secret has been received at all
        if (!isset($providerData['receivedSecret'])) {
            return false;
        }
        if (!isset($providerData['receivedDeliveryReceipt'])) {
            return false;
        }

        // prevent replay attacks
        if (!$this->verifyNoReplayAttack($providerData, $providerData['receivedSecret'])) {
            return false;
        }

        // assure that the secret is the same as required
        if (!$this->stringCompare($providerData['secret'], $providerData['receivedSecret'])) {
            return false;
        }

        // verify block state
        // we do this right now to prevent timing attacks to detect whether the
        // user is blocked
        if ($this->userIsBlocked($providerData)) {
            return false;
        }

        // assure that the current delivery receipt is *not* a decline message
        if ($providerData['receivedDeliveryReceipt'] === 4) {
            // take more drastic steps if it the user explicitly disallowed access
            $this->handleMessageDecline($providerData, $user);
            if ($context == 'login') {
                // manually need to save provider data as when verification fails this is not done by default
                $tfaModel = XenForo_Model::create('XenForo_Model_Tfa');
                $tfaModel->updateUserProvider($user['user_id'], $this->_providerId, $providerData, true);
                // usually this part of the code should never be reached as the callback/receiver
                // triggers this check and saves the resulting provider data in any case
            }
            return false; // and fail silently
        }

        // assure that the receipt message is a confirmation/acknowledge receipt
        // or has at least been a confirmation receipe before
        if ($providerData['receivedDeliveryReceipt'] !== 3 &&
            $providerData['receivedDeliveryReceiptLargest'] !== 3
        ) {
            return false;
        }

        $this->updateReplayCheckData($providerData, $providerData['receivedSecret']);

        // unregister confirmation
        $this->unregisterPendingConfirmationMessage(
            $providerData,
            ThreemaGateway_Model_TfaPendingMessagesConfirmation::PENDING_REQUEST_DELIVERY_RECEIPT
        );

        // unset data
        //
        // IMPORTANT: This is very important here as some data cannot be replay-
        // checked and would therefore result in a vulnerability.
        // Especially 'receivedDeliveryReceiptLargest' would lead to problems
        // this case when it is not reset!
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

        /** @var XenForo_Options $xenOptions */
        $xenOptions = XenForo_Application::getOptions();

        //add other options to provider data
        $providerData['useShortMessage'] = $input->filterSingle('useShortMessage', XenForo_Input::BOOLEAN);

        // default to false (if not passed/set/allowed as permissions)
        $providerData['blockedNotification'] = false;
        $providerData['blockTfaMode'] = false;
        $providerData['blockUser'] = false;
        $providerData['blockIp'] = false;

        // decline options
        if ($this->gatewayPermissions->hasPermission('blockedNotification')) {
            $providerData['blockedNotification'] = $input->filterSingle('blockedNotification', XenForo_Input::BOOLEAN);
        }
        if ($this->gatewayPermissions->hasPermission('blockTfaMode')) {
            $providerData['blockTfaMode'] = $input->filterSingle('blockTfaMode', XenForo_Input::BOOLEAN);
        }
        if ($this->gatewayPermissions->hasPermission('blockUser')) {
            $providerData['blockUser'] = $input->filterSingle('blockUser', XenForo_Input::BOOLEAN);
        }
        if ($this->gatewayPermissions->hasPermission('blockIp')) {
            $providerData['blockIp'] = $input->filterSingle('blockIp', XenForo_Input::BOOLEAN);
        }

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
            'blockedNotification' => true,
            'blockTfaMode' => true,
            'blockUser' => false,
            'blockIp' => false
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

        /** @var array $declinePermissions all permissions when declining a message */
        $declinePermissions = [
            'blockedNotification' => $this->gatewayPermissions->hasPermission('blockedNotification'),
            'blockTfaMode' => $this->gatewayPermissions->hasPermission('blockTfaMode'),
            'blockUser' => $this->gatewayPermissions->hasPermission('blockUser'),
            'blockIp' => $this->gatewayPermissions->hasPermission('blockIp'),
        ];

        // if user is admin/mod we unfortunately cannot ban them as it is not supported by XenForo
        if ($user['is_moderator'] || $user['is_admin']) {
            $declinePermissions['blockUser'] = false;
        }

        $viewParams += [
            'https' => XenForo_Application::$secure,
            'showqrcode' => $xenOptions->threema_gateway_tfa_fast_show_qr_code,
            'gatewayid' => $this->gatewaySettings->getId(),
            'blockTime' => $this->parseTime($xenOptions->threema_gateway_tfa_blocking_time * 60),
            'blockTimeDayRounded' => $this->parseTime(ThreemaGateway_Helper_General::roundToDayRelative($xenOptions->threema_gateway_tfa_blocking_time * 60, true)),
            // permissions for decline options
            'declinePermissionsSet' => in_array(true, $declinePermissions, true),
            'declinePermissions' => $declinePermissions,
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
        if (isset($providerData['receivedDeliveryReceipt'])) {
            unset($providerData['receivedDeliveryReceipt']);
        }
        if (isset($providerData['receivedDeliveryReceiptLargest'])) {
            unset($providerData['receivedDeliveryReceiptLargest']);
        }
        if (isset($providerData['triggerIp'])) {
            unset($providerData['triggerIp']);
        }
        if (isset($providerData['blocked'])) {
            unset($providerData['blocked']);
        }
        if (isset($providerData['blockedUntil'])) {
            unset($providerData['blockedUntil']);
        }
        if (isset($providerData['blockedBy'])) {
            unset($providerData['blockedBy']);
        }
        if (isset($providerData['messageDeclineHandeled'])) {
            unset($providerData['messageDeclineHandeled']);
        }
    }

    /**
     * Checks whether a user is blocked.
     *
     * @param array $providerData
     * @param bool $messageYetToSent Set to true to specify that the message
     *                              still needs to be sent
     *
     * @return bool
     */
    private function userIsBlocked(array $providerData, $messageYetToSent = false)
    {
        // not blocked when not marked as beeing blocked
        if (empty($providerData['blocked'])) {
            return false;
        }

        // check if block is not expired
        if (XenForo_Application::$time > $providerData['blockedUntil']) {
            return false;
        }

        // as message ID is evaluated below, we need to assure that it is
        // correct/already set
        if ($messageYetToSent) {
            // if the message ID is not sent yet, we know the user is blocked
            return true;
        }

        // exception: ignore blocking if the message ID is the same and the
        // delivery receipt is not a decline message (which would cause
        // another blocking)
        if ($this->stringCompare($providerData['blockedBy'], $providerData['secret']) &&
            $providerData['receivedDeliveryReceipt'] !== 4
        ) {
            // this makes it possible to 'correct' a possible wrong tap on 'decline'
            return false;
        }

        return true;
    }

    /**
     * Handles the actions when a user declines a received message.
     *
     * It can block the login for some time, ban the user temporarily or even
     * ban the IP permanently.
     *
     * @param array $providerData
     * @param array $user aray of user data
     * @param string|null $ip the current IP address
     */
    private function handleMessageDecline(array &$providerData, array $user, $ip = null)
    {
        /** @var XenForo_Options $xenOptions */
        $xenOptions = XenForo_Application::getOptions();
        /** @var int $blockingTime seconds how long users should be blocked */
        $blockingTime = $xenOptions->threema_gateway_tfa_blocking_time * 60;

        if (!$ip) {
            $ip = $providerData['triggerIp'];
        }
        // cancel, if already handeled
        if (!empty($providerData['messageDeclineHandeled'])) {
            return;
        }

        /** @var string $blockActions description of actions taken */
        $blockActions = '';

        // silently ban 2FA login
        if ($this->gatewayPermissions->hasPermission('blockTfaMode') &&
            $providerData['blockTfaMode']
        ) {
            // ban this 2FA method
            $providerData['blocked'] = true;
            $providerData['blockedBy'] = $providerData['secret'];
            $providerData['blockedUntil'] = XenForo_Application::$time + $blockingTime;

            // append to action list
            $blockActions .= ' ' . (new XenForo_Phrase('tfa_threemagw_message_blocked_login', [
                'blockTime' => $this->parseTime($blockingTime),
            ]))->render();
        }

        // ban user
        // Note that the user is not blocked from logging in, in this case;
        // they are just shown a blocking message after logging in.
        if ($this->gatewayPermissions->hasPermission('blockUser') &&
            $providerData['blockUser']
        ) {
            /** @var XenForo_DataWriter_UserBan $userBanDw */
            $userBanDw = XenForo_DataWriter::create('XenForo_DataWriter_UserBan', XenForo_DataWriter::ERROR_SILENT);
            $userBanDw->set('user_id', $user['user_id']);
            $userBanDw->set('ban_user_id', $user['user_id']);
            $userBanDw->set('user_reason', new XenForo_Phrase('threemagw_tfa_user_banned'));
            // as the ban is only lifted daily we need to set a useful day time
            $userBanDw->set('end_date',
                // round unix time to day (00:00)
                ThreemaGateway_Helper_General::roundToDay(
                    XenForo_Application::$time + $blockingTime,
                    true // round up to next full day
                )
            );
            $userBanDw->set('triggered', 1);
            $userBanDw->save();

            // append to action list
            $blockActions .= ' ' . (new XenForo_Phrase('tfa_threemagw_message_blocked_user', [
                'blockTime' => $this->parseTime(ThreemaGateway_Helper_General::roundToDayRelative($blockingTime, true)),
            ]))->render();
        }

        // ban ip
        if ($this->gatewayPermissions->hasPermission('blockIp') &&
            $providerData['blockIp']
        ) {
            /** @var XenForo_Model_Banning $ipBanModel */
            $ipBanModel = XenForo_Model::create('XenForo_Model_Banning');
            $ipBanModel->banIp($ip);

            // append to action list
            $blockActions .= ' ' . (new XenForo_Phrase('tfa_threemagw_message_blocked_ip'))->render();
        }

        // send notification message
        if ($this->gatewayPermissions->hasPermission('blockedNotification') &&
            $providerData['blockedNotification']
        ) {
            // remove unneccessary whitespace
            $blockActions = trim($blockActions);

            // add line breaks if actions were executed
            if ($blockActions) {
                $blockActions = PHP_EOL . $blockActions;
            } else {
                // theoretically we could explicitly state that nothing has
                // been done, but this is not particularly useful:
                // $blockActions = (new XenForo_Phrase('tfa_threemagw_message_blocked_nothing'))->render();
            }

            // add information that this is reversible if no permanent block actions
            // have been executed
            if (!$providerData['blockIp'] &&
                !$providerData['blockUser']
            ) {
                $blockActions = PHP_EOL . PHP_EOL . (new XenForo_Phrase('tfa_threemagw_message_blocked_canreverse'))->render();
            }

            /** @var XenForo_Options $options */
            $options = XenForo_Application::getOptions();

            /** @var XenForo_Phrase $message */
            $message = new XenForo_Phrase('tfa_threemagw_message_blocked_general', [
                'user' => $user['username'],
                'ip' => $ip,
                'blockActions' => $blockActions,
                'board' => $options->boardTitle,
                'board_url' => $options->boardUrl
            ]);

            $this->sendMessage($providerData['threemaid'], $message);
        }

        // set value to prevent duplicate handling by this method
        // This is needed as otherwise this method is executed again and again
        // if the user has not activated blockTfaMode.
        $providerData['messageDeclineHandeled'] = true;
    }
}

<?php
/**
 * 2FA callback action for handeling text messages.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Handler_Action_TfaCallback_TextMessage extends ThreemaGateway_Handler_Action_TfaCallback_Abstract
{
    /**
     * @var int replace chars in the string
     */
    const FILTER_REPLACE = 1;

    /**
     * @var int regular expression match, can only succeed or fail
     */
    const FILTER_REGEX_MATCH = 10;

    /**
     * @var string text of Threema message
     */
    protected $msgText;

    /**
     * Prepare the message handling. Should be called before any other actions.
     *
     * @return bool
     */
    public function prepareProcessing()
    {
        $this->msgText = trim($this->threemaMsg->getText());

        return true;
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
    protected function applyFilter($filterType, $filterData, $failOnError = true)
    {
        /** @var $success bool */
        $success = true;

        switch ($filterType) {
            case $this::FILTER_REPLACE:
                $success = strtr($this->msgText, $filterData);

                if (is_string($success)) {
                    $this->msgText = $success;
                    $success       = true;
                } else {
                    $success = false;
                }
                break;

            case $this::FILTER_REGEX_MATCH:
                $success = preg_match($filterData, $this->msgText);
                break;

            default:
                throw new XenForo_Exception('Unknown filter type: ' . $filterType);
                break;
        }

        if ($failOnError && !$success) {
            return false;
        }

        return true;
    }

    /**
     * Does all steps needed to do before processing data.
     *
     * Returns "false" if the process should be canceled. Otherwise "true".
     *
     * @throws XenForo_Exception
     * @return bool
     */
    protected function preProcessPending()
    {
        $this->log('Recognized ' . $this->name . ' (text message).');
        $this->log('[...]', 'Message text: ' . $this->msgText);

        if (!parent::preProcessPending()) {
            return false;
        };
    }

    /**
     * Verifies & saves data for one confirm request.
     *
     * Returns "false" if the process should be canceled. Otherwise "true".
     *
     * @param array $processOptions please include 'saveKey'
     *
     * @return bool
     */
    protected function processConfirmRequest($confirmRequest, array $processOptions = [])
    {
        if (!parent::processConfirmRequest($confirmRequest, $processOptions)) {
            return false;
        }

        // save data
        try {
            $this->setDataForRequest($confirmRequest, [
                $processOptions['saveKey'] => $this->msgText
            ]);
        } catch (Exception $e) {
            $this->log('Could not save data for request.', $e->getMessage());
        }

        // whether the code is the same as the requested one is verified in
        // the actual 2FA provider (verifyFromInput) later

        return true;
    }
}

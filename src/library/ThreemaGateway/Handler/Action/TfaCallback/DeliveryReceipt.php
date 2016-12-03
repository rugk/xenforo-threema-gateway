<?php
/**
 * 2FA callback action for handeling delivery receipt messages.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Handler_Action_TfaCallback_DeliveryReceipt extends ThreemaGateway_Handler_Action_TfaCallback_Abstract
{
    /**
     * @var int filter because of receipt type
     */
    const FILTER_RECEIPT_TYPE_EQUAL = 1;

    /**
     * @var int filter because of receipt type
     */
    const FILTER_RECEIPT_TYPE_MORETHAN = 2;

    /**
     * @var int filter because of receipt type
     */
    const FILTER_RECEIPT_TYPE_LESSTHAN = 3;

    /**
     * @var array acknowledged message IDs
     */
    protected $ackedMsgIds;

    /**
     * @var int type of delivery receipt
     */
    protected $receiptType;

    /**
     * Prepare the message handling. Should be called before any other actions.
     *
     * @return bool
     */
    public function prepareProcessing()
    {
        $this->ackedMsgIds = $this->threemaMsg->getAckedMessageIds();
        $this->receiptType = $this->threemaMsg->getReceiptType();

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
            case $this::FILTER_RECEIPT_TYPE_EQUAL:
                $success = ($this->receiptType == $filterData);
                break;

            case $this::FILTER_RECEIPT_TYPE_MORETHAN:
                $success = ($this->receiptType > $filterData);
                break;

            case $this::FILTER_RECEIPT_TYPE_LESSTHAN:
                $success = ($this->receiptType < $filterData);
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
        // as the acknowledged message IDs cannot be evaluated at this point of
        // time one cannot be sure that th acknowledged message really belongs
        // to a 2FA message.
        // ThatÄs the reason for the "potential" here.
        $this->log('Recognized potential ' . $this->name . ' (delivery message).');

        if (!parent::preProcessPending()) {
            return false;
        };
    }

    /**
     * Verifies & saves data for one confirm request.
     *
     * Returns "false" if the process should be canceled. Otherwise "true".
     *
     * @param array $processOptions please include 'saveKey',
     *                              'saveKeyReceiptType' and
     *                              'saveKeyReceiptTypeLargest'
     *
     * @return bool
     */
    protected function processConfirmRequest($confirmRequest, array $processOptions = [])
    {
        if (!parent::processConfirmRequest($confirmRequest, $processOptions)) {
            return false;
        }

        /** @var bool $successfullyProcessed */
        $successfullyProcessed = false;

        // go through each message ID and try to save data
        foreach ($this->ackedMsgIds as $ackedMsgId) {
            $ackedMsgId = $this->getCryptTool()->bin2hex($ackedMsgId);

            // check whether we are requested to handle this message
            if (!$this->getCryptTool()->stringCompare($confirmRequest['extra_data'], $ackedMsgId)) {
                continue;
            }

            $this->log('Found acknowledged message ID ' . $ackedMsgId . ' of the in delivery message.');

            // save data
            try {
                $this->setDataForRequest($confirmRequest, [
                    $processOptions['saveKey'] => $ackedMsgId,
                    $processOptions['saveKeyReceiptType'] => $this->receiptType
                    // saveKeyReceiptTypeLargest is set by preSaveData() as it needs to
                    // analyse the old data
                ], $processOptions);
            } catch (Exception $e) {
                $this->log('Could not save data for request.', $e->getMessage());
            }

            // whether the code is the same as the requested one is verified in
            // the actual 2FA provider (verifyFromInput) later

            $successfullyProcessed = true;
        }

        if (!$successfullyProcessed) {
            $this->log('It turned out the message actually seems to be a delivery message unrelated to this 2FA mode.');
        }

        return $successfullyProcessed;
    }

    /**
     * Checks whether the previously saved receipt type is smaller than the
     * one got currently.
     *
     * @param array $oldProviderData old data read
     * @param array $setData         new data to set
     * @param array $processOptions  custom options (optional)
     *
     * @throws XenForo_Exception
     * @return bool
     */
    protected function preSaveData(array &$oldProviderData, array &$setData, array $processOptions = [])
    {
        if ($processOptions['saveKeyReceiptTypeLargest']) {
            if (!isset($oldProviderData[$processOptions['saveKeyReceiptTypeLargest']]) ||
                $oldProviderData[$processOptions['saveKeyReceiptTypeLargest']] < $this->receiptType
            ) {
                $setData[$processOptions['saveKeyReceiptTypeLargest']] = $this->receiptType;
            }
        }

        return true;
    }
}

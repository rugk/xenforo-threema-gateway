<?php
/**
 * CleanUp tasks executed via cron.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_CronEntry_CleanUp
{
    /**
     * This task removes all deleted messages, which are so old that they are
     * no risk for a reply attack anymore.
     *
     * This ensures that unnecessary meta data is deleted as afterwards also
     * the message ID is deleted so no one knows that the message has been
     * received.
     */
    public static function pruneOldDeletedMessages()
    {
        /** @var $messageModel ThreemaGateway_Model_Messages */
        $messageModel = XenForo_Model::create('ThreemaGateway_Model_Messages');
        $messageModel->setTimeLimit(null, ThreemaGateway_Helper_Message::getOldestPossibleReplayAttackDate(), 'date_received');

        /* @var XenForo_Options $options */
        $xenOptions = XenForo_Application::getOptions();
        // only need to test whether one attribute is invalid, all others are
        // automatically invalid too unless something is really went wrong
        /* @var array $condition */
        $conditions = ['date_send IS NULL'];

        // when the hardened mode is activated, only set receive date to "null"
        if ($xenOptions->threema_gateway_harden_reply_attack_protection) {
            // update all receive_dates with "null" to remove as much meta data
            // as possible
            $messageModel->removeMetaData($conditions, ['date_received']);
        } else {
            // remove whole data set
            $messageModel->removeMetaData($conditions);
        }
    }

    /**
     * This task removes all expired pending requests for the 2FA modes.
     *
     * This task should stay enabled.
     */
    public static function pruneExpiredTfaPendingRequests()
    {
        /** @var $messageModel ThreemaGateway_Model_Messages */
        $messageModel = XenForo_Model::create('ThreemaGateway_Model_TfaPendingMessagesConfirmation');
        $messageModel->deleteExpired();
    }
}

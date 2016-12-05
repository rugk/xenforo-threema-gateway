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
     * Disabling this is not critical, just your database gets filled up a bit
     * more. If you disable this replay attacks can be prevented even if the
     * Gateway server sends malicious data with wrong dates.
     *
     */
    public static function pruneOldDeletedMessages()
    {
        /** @var $messageModel ThreemaGateway_Model_Messages */
        $messageModel = XenForo_Model::create('ThreemaGateway_Model_Messages');
        $messageModel->setTimeLimit(null, ThreemaGateway_Helper_Message::getOldestPossibleReplayAttackDate(), 'date_received');

        // only need to test whether one attribute is invalid, all others are
        // automatically invalid too unless something really went wrong
        $messageModel->removeMetaData(['date_send IS NULL']);
    }

    /**
     * This task removes all expired pending requests for the 2FA modes.
     *
     * This task should stay enabled.
     *
     */
    public static function pruneExpiredTfaPendingRequests()
    {
        /** @var $messageModel ThreemaGateway_Model_Messages */
        $messageModel = XenForo_Model::create('ThreemaGateway_Model_TfaPendingMessagesConfirmation');
        $messageModel->deleteExpired();
    }
}

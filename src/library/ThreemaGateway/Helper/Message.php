<?php
/**
 * Helper for cron tasks.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

class ThreemaGateway_Helper_Message
{
    /**
     * Checks whether a message is at risk of an replay attack.
     *
     * If the message is not at risk, it is usually just too old, so
     * that date checking mechanisms would prevent it to be delivered.
     *
     * @param array $messageMetaData the message data including, at least, the
     *                               meta data.
     *
     * @return bool
     */
    public static function isAtRiskOfReplayAttack(array $messageMetaData)
    {
        // in case the time is no valid/positive number, better return true
        if (!$messageMetaData['date_received']) {
            return true;
        }

        /* @var XenForo_Options $options */
        $xenOptions = XenForo_Application::getOptions();

        // when the hardened mode is activated, always return true
        if ($xenOptions->threema_gateway_harden_reply_attack_protection) {
            return true;
        }

        // if message has not been send at least 2 weeks ago (by default), it is attackable
        if ($messageMetaData['date_received'] >= self::getOldestPossibleReplayAttackDate()) {
            return true;
        }

        // older messages are fine
        return false;
    }

    /**
     * Returns the date/time where a message would still be accepted altghough
     * it is outdated.
     *
     * Note that for doing the actual replay attack check, this method *must not*
     * be used, but the option should rather be used directly.
     *
     * @return int
     */
    public static function getOldestPossibleReplayAttackDate()
    {
        /** @var XenForo_Options $options */
        $options   = XenForo_Application::getOptions();
        /** @var int $rejectOlDefault the default maximum age of a message according*/
        $rejectOlDefault = strtotime('-14 days', XenForo_Application::$time);
        /* @var int $rejectOld the maximum age of a message according to the options */
        $rejectOldOption = '';
        if ($options->threema_gateway_verify_receive_time && $options->threema_gateway_verify_receive_time['enabled']) {
            $rejectOldOption = strtotime($options->threema_gateway_verify_receive_time['time'], XenForo_Application::$time);
        } else {
            $rejectOldOption = $rejectOlDefault;
        }

        return min($rejectOlDefault, $rejectOldOption);
    }
}

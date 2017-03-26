<?php
/**
 * Model for temporare action log.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Model_ActionThrottle extends XenForo_Model
{
    /**
     * @var string database table name
     */
    const DB_TABLE = 'xf_threemagw_action_throttle';

    /**
     * Logs the fact that the user executed a particular action.
     *
     * @param int    $userId
     * @param string $actionType
     */
    public function logAction($userId, $actionType)
    {
        $dataWriter = XenForo_DataWriter::create('ThreemaGateway_DataWriter_ActionThrottle');
        $dataWriter->set('user_id', $userId);
        $dataWriter->set('action_date', XenForo_Application::$time);
        $dataWriter->set('action_type', $actionType);
        return $dataWriter->save();
    }

    /**
     * Clears the whole action log for a particular action for a user.
     *
     * @param int    $userId
     * @param string $actionType
     */
    public function clearActionLogForUser($userId, $actionType)
    {
        $this->_getDb()->delete(self::DB_TABLE, [
            'user_id = ?' => $userId,
            'action_type = ?' => $actionType,
        ]);
    }

    /**
     * Returns the count of a specific action, which was executed by a particular
     * user since a specific time.
     *
     * @param int    $userId     the affected user
     * @param string $actionType describe the action to be count
     * @param int    $timeLimit  the oldest date, where actions should be "cut off"
     *
     * @return int
     */
    public function getActionsInTime($userId, $actionType, $timeLimit)
    {
        return $this->_getDb()->fetchOne('
            SELECT COUNT(*)
            FROM `' . self::DB_TABLE . '`
            WHERE action_date > ? AND
                action_type = ? AND
                user_id = ?
        ', [$timeLimit, $actionType, $userId]);
    }

    /**
     * Checks whether a user is rate-limited concerning a specific action.
     *
     * @param int    $userId     the affected user
     * @param string $actionType describe the action to be checked
     *
     * @return bool
     */
    public function isLimited($userId, $actionType)
    {
        $limits = $this->getActionLimit($actionType);
        foreach ($limits AS $limit) {
            $timeLimit = $limit[0];
            $actionLimit = $limit[1];

            $actionExecuted = $this->getActionsInTime($userId, $actionType, XenForo_Application::$time - $timeLimit);
            if ($actionExecuted >= $actionLimit) {
                return true;
            }
        }

        return false;
    }

    /**
     * Removes old action log entries.
     *
     * @param int    $timeLimit  the oldest date, where actions are saved
     */
    public function pruneActionLog($timeLimit = null)
    {
        if ($timeLimit === null) {
            $timeLimit = XenForo_Application::$time - 3600; // 1h before
        }

        $this->_getDb()->delete(self::DB_TABLE, [
            'action_date < ?' => $timeLimit
        ]);
    }

    /**
     * Returns the number of actions a user is allowed to do in an specific time.
     *
     * THe returned array has the format:
     * [time, max number of actions to be executed]
     *
     * @param string $actionType
     *
     * @return array
     */
    protected function getActionLimit($actionType)
    {
        /** @var array $limitArray */
        $limitArray = [];
        /** @var XenForo_Options $xenOptions */
        $xenOptions = XenForo_Application::getOptions();

        switch ($actionType) {
            case 'send':
                if ($xenOptions->threema_gateway_throttle_1min > 0) {
                    $limitArray[] = [60, $xenOptions->threema_gateway_throttle_1min];
                }
                if ($xenOptions->threema_gateway_throttle_5min > 0) {
                    $limitArray[] = [60 * 5, $xenOptions->threema_gateway_throttle_5min];
                }
                if ($xenOptions->threema_gateway_throttle_5h > 0) {
                    $limitArray[] = [60 * 60, $xenOptions->threema_gateway_throttle_5h];
                }
                break;
        }

        return $limitArray;
    }
}

<?php
/**
 * Model for querying pending confirmation messages.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Model_TfaPendingMessagesConfirmation extends XenForo_Model
{
    /**
     * @var string database table name
     */
    const DbTable = 'xf_threemagw_tfa_pending_msgs_confirm';

    /**
     * @var int Pending type: a 6 digit code is requested
     */
    const PENDING_REQUESTCODE = 1;

    /**
     * Returns the pending confirmations by a given request ID.
     *
     * @param string $requestId
     *
     * @return null|array
     */
    public function getPendingById($requestId)
    {
        /* @var mixed result of SQL query */
        $result = $this->_getDb()->fetchRow('SELECT * FROM `' . self::DbTable . '`
                  WHERE `request_id` = ?',
                  $requestId);

        if (!$result) {
            return null;
        }

        return $result;
    }

    /**
     * Returns the pending confirmations by Threema ID and optionally also by
     * the pending type.
     *
     * @param string $threemaId
     * @param int    $pendingType use the PENDING_* constants
     *
     * @return null|array
     */
    public function getPending($threemaId, $pendingType = null)
    {
        /** @var array $conditionsArray */
        $conditionsArray = [
            '`threema_id` = ?'
        ];
        $paramsArray = [
            $threemaId
        ];

        if ($pendingType !== null) {
            $conditionsArray[] = '`pending_type` = ?';
            $paramsArray[]     = $pendingType;
        }

        /* @var mixed result of SQL query */
        $result = $this->fetchAllKeyed('SELECT * FROM `' . self::DbTable . '`
                  WHERE ' . $this->getConditionsForClause($conditionsArray),
                  'request_id', $paramsArray);

        if (!$result) {
            return null;
        }

        return $result;
    }
}

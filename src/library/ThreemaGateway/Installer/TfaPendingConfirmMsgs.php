<?php
/**
 * Adds/Deletes the pending message confirm table.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

/**
 * Methods for creating or deleting the message confirm table.
 */
class ThreemaGateway_Installer_TfaPendingConfirmMsgs
{
    /**
     * @var string database table name
     */
    const DbTable = 'xf_threemagw_tfa_pending_msgs_confirm';

    /**
     * Create a new keystore (table) in the database.
     */
    public function create()
    {
        $db = XenForo_Application::get('db');
        $db->query('CREATE TABLE `' . self::DbTable . '`
            (`request_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `session_id` VARBINARY(32) NOT NULL,
            `threema_id` CHAR(8) NOT NULL,
            `pending_type` TINYINT(3) UNSIGNED NOT NULL,
            `session_key` VARCHAR(64) NOT NULL,
            `expiry_date` INT(10) UNSIGNED NOT NULL,
            PRIMARY KEY(`request_id`),
            INDEX(`threema_id`),
            INDEX(`pending_type`),
            INDEX(`expiry_date`)
            ) COMMENT=\'Stores pending 2FA requests for confirmation messages for receiver/callback.\'');
    }

    /**
     * Deletes the keystore (table).
     */
    public function destroy()
    {
        $db = XenForo_Application::get('db');
        $db->query('DROP TABLE `' . self::DbTable . '`');
    }
}

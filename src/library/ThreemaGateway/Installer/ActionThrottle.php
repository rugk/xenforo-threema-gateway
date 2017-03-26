<?php
/**
 * Adds/Deletes the action throttle table.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2017 rugk
 * @license MIT
 */

/**
 * Methods for creating or deleting the action throttle table.
 */
class ThreemaGateway_Installer_ActionThrottle
{
    /**
     * @var string database table name
     */
    const DB_TABLE = 'xf_threemagw_action_throttle';

    /**
     * Create a new table in the database.
     */
    public function create()
    {
        $db = XenForo_Application::get('db');
        $db->query('CREATE TABLE `' . self::DB_TABLE . '`
            (`action_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT(10) UNSIGNED NOT NULL,
            `action_date` INT(10) UNSIGNED NOT NULL,
            `action_type` VARBINARY(25) NOT NULL,
            PRIMARY KEY(`action_id`)
            ) COMMENT=\'Temporarily logs Threema Gateway actions of users in order to limit them.\'');
    }

    /**
     * Deletes the table.
     */
    public function destroy()
    {
        $db = XenForo_Application::get('db');
        $db->query('DROP TABLE `' . self::DB_TABLE . '`');
    }
}

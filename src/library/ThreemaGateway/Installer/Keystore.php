<?php
/**
 * Adds/Deletes the keystore.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

/**
 * Methods for creating or deleting the keystore table.
 */
class ThreemaGateway_Installer_Keystore
{
    /**
     * @var string database table name
     */
    const DB_TABLE = 'xf_threemagw_keystore';

    /**
     * Create a new keystore (table) in the database.
     */
    public function create()
    {
        $db = XenForo_Application::get('db');
        $db->query('CREATE TABLE `' . self::DB_TABLE . '`
            (`threema_id` CHAR(8) NOT NULL PRIMARY KEY,
            `public_key` CHAR(64) NOT NULL)
            ');
    }

    /**
     * Deletes the keystore (table).
     */
    public function destroy()
    {
        $db = XenForo_Application::get('db');
        $db->query('DROP TABLE `' . self::DB_TABLE . '`');
    }
}

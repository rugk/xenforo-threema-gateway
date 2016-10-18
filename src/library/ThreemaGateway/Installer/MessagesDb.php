<?php
/**
 * Adds/Deletes the message tables.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

/**
 * Methods for creating or deleting the message tables.
 */
class ThreemaGateway_Installer_MessagesDb
{
    /**
     * @var string database table prefix
     */
    const DbTablePrefix = 'xf_threemagw';

    /**
     * Create a new message tables in the database.
     */
    public function create()
    {
        $db = XenForo_Application::get('db');

        // set charset
        $db->query('SET NAMES utf8mb4');

        // main table
        $db->query('CREATE TABLE `' . self::DbTablePrefix . '_messages`
            (`message_id` CHAR(16),
            `message_type_code` CHAR(64) NOT NULL COMMENT \'determinates type of message\',
            `sender_treema_id` CHAR(8) NOT NULL,
            `date_send` DATETIME NOT NULL COMMENT \'the date/time delivered by the Gateway server\',
            `date_received` DATETIME NOT NULL COMMENT \'the date/time when msg was received by this server\',
            PRIMARY KEY (`message_id`)
            )');

        // files associated with messages
        $db->query('CREATE TABLE `' . self::DbTablePrefix . '_files`
            (`file_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `message_id` CHAR(16) NOT NULL,
            `file_path` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
            `is_saved` BOOLEAN DEFAULT true,
            PRIMARY KEY (`file_id`),
            FOREIGN KEY (`message_id`) REFERENCES ' . self::DbTablePrefix . '_messages(`message_id`)
            )');

        // text messages
        $db->query('CREATE TABLE `' . self::DbTablePrefix . '_messages_text`
            (`message_id` CHAR(16) NOT NULL,
            `text` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
            PRIMARY KEY (`message_id`),
            FOREIGN KEY (`message_id`) REFERENCES ' . self::DbTablePrefix . '_messages(`message_id`)
            )');

        // delivery receipt
        $db->query('CREATE TABLE `' . self::DbTablePrefix . '_messages_delivery_receipt`
            (`message_id` CHAR(16) NOT NULL,
            `receipt_type` TINYINT(3) UNSIGNED,
            PRIMARY KEY (`message_id`),
            FOREIGN KEY (`message_id`) REFERENCES ' . self::DbTablePrefix . '_messages(`message_id`)
            )');

        // file message
        $db->query('CREATE TABLE `' . self::DbTablePrefix . '_messages_file`
            (`message_id` CHAR(16) NOT NULL,
            `file_size` INT(10) UNSIGNED,
            `file_name` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
            `mime_type` VARCHAR(255),
            PRIMARY KEY (`message_id`),
            FOREIGN KEY (`message_id`) REFERENCES ' . self::DbTablePrefix . '_messages(`message_id`)
            )');

        // image message
        $db->query('CREATE TABLE `' . self::DbTablePrefix . '_messages_image`
            (`message_id` CHAR(16) NOT NULL,
            `file_size` INT(10) UNSIGNED,
            PRIMARY KEY (`message_id`),
            FOREIGN KEY (`message_id`) REFERENCES ' . self::DbTablePrefix . '_messages(`message_id`)
            )');
    }

    /**
     * Deletes all message tables.
     */
    public function destroy()
    {
        $db = XenForo_Application::get('db');
        $db->query('DROP TABLE `' . self::DbTablePrefix . '_messages_delivery`');
        $db->query('DROP TABLE `' . self::DbTablePrefix . '_messages_file`');
        $db->query('DROP TABLE `' . self::DbTablePrefix . '_messages_image`');
        $db->query('DROP TABLE `' . self::DbTablePrefix . '_messages_text`');
        $db->query('DROP TABLE `' . self::DbTablePrefix . '_files`');
        $db->query('DROP TABLE `' . self::DbTablePrefix . '_messages`');
    }
}

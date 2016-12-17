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
    const DB_TABLE_PREFIX = 'xf_threemagw';

    /**
     * Create a new message tables in the database.
     */
    public function create()
    {
        $db = XenForo_Application::get('db');

        // set charset
        $db->query('SET NAMES utf8mb4');

        // main table
        $db->query('CREATE TABLE `' . self::DB_TABLE_PREFIX . '_messages`
            (`message_id` CHAR(16),
            `message_type_code` ENUM(' . $this->getMsgTypes() . ') COMMENT \'determinates type of message\',
            `sender_threema_id` CHAR(8),
            `date_send` INT UNSIGNED COMMENT \'the date/time delivered by the Gateway server stored as unix timestamp\',
            `date_received` INT UNSIGNED COMMENT \'the date/time when msg was received by this server stored as unix timestamp\',
            PRIMARY KEY (`message_id`)
            )');
        // here "null" is allowed as for deleted messages the message ID is
        // still stored, which prevents replay attacks as a message ID cannot
        // be reused in this case

        // files associated with messages
        $db->query('CREATE TABLE `' . self::DB_TABLE_PREFIX . '_files`
            (`file_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `message_id` CHAR(16) NOT NULL,
            `file_path` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
            `file_type` VARCHAR(100) NOT NULL,
            `is_saved` BOOLEAN NOT NULL DEFAULT true,
            PRIMARY KEY (`file_id`),
            FOREIGN KEY (`message_id`) REFERENCES ' . self::DB_TABLE_PREFIX . '_messages(`message_id`)
            ) COMMENT=\'Stores files associated with messages.\'');

        // text messages
        $db->query('CREATE TABLE `' . self::DB_TABLE_PREFIX . '_messages_text`
            (`message_id` CHAR(16) NOT NULL,
            `text` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
            PRIMARY KEY (`message_id`),
            FOREIGN KEY (`message_id`) REFERENCES ' . self::DB_TABLE_PREFIX . '_messages(`message_id`)
            )');

        // delivery receipt
        $db->query('CREATE TABLE `' . self::DB_TABLE_PREFIX . '_messages_delivery_receipt`
            (`message_id` CHAR(16) NOT NULL,
            `receipt_type` TINYINT UNSIGNED NOT NULL,
            PRIMARY KEY (`message_id`)
            FOREIGN KEY (`message_id`) REFERENCES ' . self::DB_TABLE_PREFIX . '_messages(`message_id`)
            )');

        // file message
        $db->query('CREATE TABLE `' . self::DB_TABLE_PREFIX . '_messages_file`
            (`message_id` CHAR(16) NOT NULL,
            `file_size` INT UNSIGNED NOT NULL,
            `file_name` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
            `mime_type` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`message_id`),
            FOREIGN KEY (`message_id`) REFERENCES ' . self::DB_TABLE_PREFIX . '_messages(`message_id`)
            )');

        // image message
        $db->query('CREATE TABLE `' . self::DB_TABLE_PREFIX . '_messages_image`
            (`message_id` CHAR(16) NOT NULL,
            `file_size` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`message_id`),
            FOREIGN KEY (`message_id`) REFERENCES ' . self::DB_TABLE_PREFIX . '_messages(`message_id`)
            )');

        // acknowledged messages associated with delivery receipt messages
        $db->query('CREATE TABLE `' . self::DB_TABLE_PREFIX . '_ackmsgs`
            (`ack_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `message_id` CHAR(16) NOT NULL COMMENT \'the id of the delivery receipt message, which acknowledges other messages\',
            `ack_message_id` CHAR(16) NOT NULL COMMENT \'the id of the message, which has been acknowledged \',
            PRIMARY KEY(`ack_id`),
            FOREIGN KEY (`message_id`) REFERENCES ' . self::DB_TABLE_PREFIX . '_messages(`message_id`),
            INDEX(`ack_message_id`)
            ) COMMENT=\'Stores acknowledged message IDs.\'');
    }

    /**
     * Deletes all message tables.
     */
    public function destroy()
    {
        $db = XenForo_Application::get('db');
        $db->query('DROP TABLE `' . self::DB_TABLE_PREFIX . '_ackmsgs`');
        $db->query('DROP TABLE `' . self::DB_TABLE_PREFIX . '_messages_file`');
        $db->query('DROP TABLE `' . self::DB_TABLE_PREFIX . '_messages_delivery_receipt`');
        $db->query('DROP TABLE `' . self::DB_TABLE_PREFIX . '_messages_image`');
        $db->query('DROP TABLE `' . self::DB_TABLE_PREFIX . '_messages_text`');
        $db->query('DROP TABLE `' . self::DB_TABLE_PREFIX . '_files`');
        $db->query('DROP TABLE `' . self::DB_TABLE_PREFIX . '_messages`');
    }

    /**
     * Returns a string of all available message types.
     *
     * @return string
     */
    protected function getMsgTypes()
    {
        $receiver = new ThreemaGateway_Handler_Action_Receiver;
        return implode(', ', $receiver->getTypesArray());
    }
}

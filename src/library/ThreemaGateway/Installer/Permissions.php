<?php
/**
 * Adds/Deletes the addon permissions.
 *
 * You can use this MySQL query to show all permissions of the add-on:
 * SELECT * FROM `xf_permission_entry` WHERE `permission_group_id` = 'threemagw'
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

class ThreemaGateway_Installer_Permissions
{
    /**
     * @var The group id of all permissions.
     */
    const GroupId = 'threemagw';

    /**
     * Sets an "allow" rule for a specific user group.
     *
     * @param int    $userGroupId        The user group:
     *                                   1 = unconfirmed
     *                                   2 = registered
     *                                   3 = administrator
     *                                   4 = moderator
     * @param string $applyPermissionId  The permission id
     * @param string $permissionValue    (optional) The value of the permission: allow, deny
     *                                   use_int
     * @param int    $permissionValueInt (optional) When using an integer (use_int)
     *                                   specify the integer to store
     */
    public function addForUserGroup($userGroupId, $permissionId, $permissionValue, $permissionValueInt = 0)
    {
        $db = XenForo_Application::get('db');

        $db->query('INSERT ' . (XenForo_Application::get('options')->enableInsertDelayed ? 'DELAYED' : '') . ' IGNORE INTO xf_permission_entry
                (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
                VALUES (?, 0, ?, ?, ?, ?)',
                [$userGroupId, self::GroupId, $permissionId, $permissionValue, $permissionValueInt]);
    }

    /**
     * Deletes all permissons of the specified group id.
     */
    public function deleteAll()
    {
        $db = XenForo_Application::get('db');

        $db->query('DELETE FROM xf_permission_entry
                WHERE permission_group_id=?',
                [self::GroupId]);
    }
}

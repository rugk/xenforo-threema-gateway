<?php
/**
 * Allows adding and removing providers.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

/**
 * Methods for writeing or reading from the DB.
 */
class ThreemaGateway_Installer_TfaProvider
{
    /**
     * @var string $TfaId The unique ID of the providet
     */
    protected $TfaId;

    /**
     * @var string $TfaClass The class which handles 2FA requests
     */
    protected $TfaClass;

    /**
     * @var int $TfaClass A number, which represents the priority of the
     *          provider.
     */
    protected $TfaPriority;

    /**
     * Add a new provider to the database.
     *
     * @param string $TfaId       The unique ID of the provider
     * @param string $TfaClass    The class which handles 2FA requests
     * @param int    $TfaPriority
     */
    public function __construct($TfaId, $TfaClass, $TfaPriority)
    {
        $this->TfaId       = $TfaId;
        $this->TfaClass    = $TfaClass;
        $this->TfaPriority = $TfaPriority;
    }

    /**
     * Add the new provider to the database.
     *
     * @param bool|int $enabled (optional) Whether the provider should
     *                          be activated or disabled.
     */
    public function add($enabled = true)
    {
        $db = XenForo_Application::get('db');
        $db->query('INSERT ' . (XenForo_Application::get('options')->enableInsertDelayed ? 'DELAYED' : '') . ' INTO `xf_tfa_provider`
                  (`provider_id`, `provider_class`, `priority`, `active`)
                  VALUES (?, ?, ?, ?)',
                  [$this->TfaId, $this->TfaClass, $this->TfaPriority, (int) $enabled]);
    }

    /**
     * Delete the provider from the database.
     */
    public function delete()
    {
        $db = XenForo_Application::get('db');
        // delete user data
        $db->delete('xf_user_tfa', [
            'provider_id = ?' => $providerId
        ]);

        // unfortunately I do not want to go through each deleted user data here
        // to test whether the user has no 2FA mode anymore as it is done in
        // XenForo_Model_Tfa->disableTfaForUser().
        // I have not experienced any issues when this is not done and when the
        // deleted data is stored there this is not very bad.

        // delete provider itself
        $db->query('DELETE FROM `xf_tfa_provider`
                    WHERE `provider_id`=?',
                    [$this->TfaId]);
    }
}

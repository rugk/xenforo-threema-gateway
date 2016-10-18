<?php
/**
 * Add-on installer/uninstaller.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
*/

/**
 * Installation and uninstallation routines for Threema Gateway.
 */
class ThreemaGateway_Installer
{
    /**
     * Returns the provider models needed for modifying the database.
     *
     * @return array
     */
    public static function getProviderModells()
    {
        /* @var array An array with the modells of all providers */
        $ProviderModels = [];

        // add provider
        $ProviderModels['conventional'] = new ThreemaGateway_Installer_TfaProvider(
            ThreemaGateway_Constants::TfaIDprefix . 'conventional',
            'ThreemaGateway_Tfa_Conventional',
            ThreemaGateway_Constants::TfaBasePriority - 5);

        return $ProviderModels;
    }

    /**
     * At the installation this will check the XenForo version and
     * add the 2FA provider to the table.
     *
     * @param array $installedAddon
     */
    public static function install($installedAddon)
    {
        /* @var array An array with the models of all providers */
        $ProviderModells = self::getProviderModells();

        // check requirements of Gateway
        if (!self::meetsRequirements($error)) {
            throw new XenForo_Exception($error);
        }

        // Get installed add-on version
        $OldAddonVersion = is_array($installedAddon) ? $installedAddon['version_id'] : 0;

        // Not installed
        if ($OldAddonVersion == 0) {
            // add tfa providers to database
            foreach ($ProviderModells as $provider) {
                $provider->add();
            }

            // add permissions
            /** @var ThreemaGateway_Installer_Permissions $permissionsInstaller */
            $permissionsInstaller = new ThreemaGateway_Installer_Permissions;
            $permissionsInstaller->addForUserGroup(2, 'use', 'allow');
            $permissionsInstaller->addForUserGroup(2, 'send', 'allow');
            $permissionsInstaller->addForUserGroup(2, 'receive', 'allow');
            $permissionsInstaller->addForUserGroup(2, 'fetch', 'allow');
            $permissionsInstaller->addForUserGroup(2, 'lookup', 'allow');
            $permissionsInstaller->addForUserGroup(2, 'tfa', 'allow');

            // create public key store
            /** @var ThreemaGateway_Installer_Keystore $keystoreInstaller */
            $keystoreInstaller = new ThreemaGateway_Installer_Keystore;
            $keystoreInstaller->create();

            // create custom user field
            /** @var XenForo_DataWriter $userFieldWriter */
            $userFieldWriter = XenForo_DataWriter::create('XenForo_DataWriter_UserField');
            $userFieldWriter->set('field_id', 'threemaid');
            $userFieldWriter->set('display_group', 'contact');
            $userFieldWriter->set('display_order', 120);
            $userFieldWriter->set('field_type', 'textbox');
            $userFieldWriter->set('match_type', 'callback');
            $userFieldWriter->set('match_callback_class', 'ThreemaGateway_Helper_UserField');
            $userFieldWriter->set('match_callback_method', 'verifyThreemaId');
            $userFieldWriter->set('max_length', 8);
            $userFieldWriter->save();

            // create tables for messages
            /* @var ThreemaGateway_Installer_MessagesDb */
            $messageDbInstaller = new ThreemaGateway_Installer_MessagesDb;
            $messageDbInstaller->create();
        }
    }

    /**
     * When uninstalling this deletes the unneccessary database entries/tables.
     */
    public static function uninstall()
    {
        // remove message tables
        /* @var ThreemaGateway_Installer_MessagesDb */
        $messageDbInstaller = new ThreemaGateway_Installer_MessagesDb;
        $messageDbInstaller->destroy();

        /* @var array An array with the modells of all providers */
        $ProviderModells = self::getProviderModells();

        // delete tfa provider from database
        foreach ($ProviderModells as $provider) {
            $provider->delete();
        }

        // delete keystore
        /** @var ThreemaGateway_Installer_Keystore $keystoreInstaller */
        $keystoreInstaller = new ThreemaGateway_Installer_Keystore;
        $keystoreInstaller->destroy();

        // delete permissions
        /** @var ThreemaGateway_Installer_Permissions $permissionsInstaller */
        $permissionsInstaller = new ThreemaGateway_Installer_Permissions;
        $permissionsInstaller->deleteAll();

        // delete custom user field (if it exists)
        $userFieldModel = new XenForo_Model_UserField;
        if ($userFieldModel->getUserFieldById('threemaid')) {
            /** @var XenForo_DataWriter $userFieldWriter  */
            $userFieldWriter = XenForo_DataWriter::create('XenForo_DataWriter_UserField');
            $userFieldWriter->setExistingData('threemaid');
            $userFieldWriter->delete();
        }

        /* @var XenForo_Options */
        $xenOptions = XenForo_Application::getOptions();

        //delete debug log files
        ThreemaGateway_Option_DebugModeLog::removeLog($xenOptions->threema_gateway_logreceivedmsgs['path']);
    }

    /**
     * At the installation this will check the XenForo version and
     * remove the 2FA provider to the table.
     *
     * @param  string $error Will be filled by a human-readable error description
     *                       when an error occurs
     * @return bool
     */
    public static function meetsRequirements(&$error)
    {
        // check XenForo version
        if (XenForo_Application::$versionId < 1050051) {
            $error = 'This add-on requires XenForo 1.5.0 or higher.';
            return false;
        }

        // check PHP version
        if (version_compare(PHP_VERSION, '5.4', '<')) {
            $error = 'Threema Gateway requires PHP version 5.4 or higher. Current version: ' . PHP_VERSION;
            return false;
        }

        return true;
    }
}

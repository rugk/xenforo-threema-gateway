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
     * At the installation this will check the XenForo version and
     * add the 2FA provider to the table.
     *
     * @param array $installedAddon
     */
    public static function install($installedAddon)
    {
        /** @var array $providerInstaller An array with the models of all providers */
        $providerInstaller = self::getProviderInstaller();

        // check requirements of Gateway
        if (!self::meetsRequirements($error)) {
            throw new XenForo_Exception($error);
        }

        // Get installed add-on version
        /** @var int $oldAddonVersion internal version number of installed addon version */
        $oldAddonVersion = is_array($installedAddon) ? $installedAddon['version_id'] : 0;

        // Not installed
        if ($oldAddonVersion == 0) {
            // add tfa providers to database
            foreach ($providerInstaller as $provider) {
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
            /** @var ThreemaGateway_Installer_MessagesDb $messageDbInstaller */
            $messageDbInstaller = new ThreemaGateway_Installer_MessagesDb;
            $messageDbInstaller->create();

            // create tfa tables
            /** @var ThreemaGateway_Installer_TfaPendingConfirmMsgs $prendingConfirmMsgInstaller */
            $prendingConfirmMsgInstaller = new ThreemaGateway_Installer_TfaPendingConfirmMsgs;
            $prendingConfirmMsgInstaller->create();
        }
    }

    /**
     * When uninstalling this deletes the unneccessary database entries/tables.
     */
    public static function uninstall()
    {
        // delete tfa tables
        /** @var ThreemaGateway_Installer_TfaPendingConfirmMsgs $prendingConfirmMsgInstaller */
        $prendingConfirmMsgInstaller = new ThreemaGateway_Installer_TfaPendingConfirmMsgs;
        $prendingConfirmMsgInstaller->destroy();

        // remove message tables
        /** @var ThreemaGateway_Installer_MessagesDb $messageDbInstaller */
        $messageDbInstaller = new ThreemaGateway_Installer_MessagesDb;
        $messageDbInstaller->destroy();

        // TODO: remove user data of provider via XenForo_Model_Tfa

        /** @var array $providerInstaller An array with the models of all providers */
        $providerInstaller = self::getProviderInstaller();

        // delete tfa provider from database
        foreach ($providerInstaller as $provider) {
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
        /** @var XenForo_Model_UserField $userFieldModel */
        $userFieldModel = XenForo_Model::create('XenForo_Model_UserField');
        if ($userFieldModel->getUserFieldById('threemaid')) {
            /** @var XenForo_DataWriter $userFieldWriter */
            $userFieldWriter = XenForo_DataWriter::create('XenForo_DataWriter_UserField');
            $userFieldWriter->setExistingData('threemaid');
            $userFieldWriter->delete();
        }

        /** @var XenForo_Options $xenOptions */
        $xenOptions = XenForo_Application::getOptions();

        //delete debug log files
        ThreemaGateway_Option_DebugModeLog::removeLog($xenOptions->threema_gateway_logreceivedmsgs);
    }

    /**
     * Returns the provider installer needed for modifying the database.
     *
     * @return array
     */
    protected static function getProviderInstaller()
    {
        /** @var array $providerInstaller An array with the models of all providers */
        $providerInstaller = [];

        // add provider
        $providerInstaller['conventional'] = new ThreemaGateway_Installer_TfaProvider(
            ThreemaGateway_Constants::TfaIDprefix . '_conventional',
            'ThreemaGateway_Tfa_Conventional',
            ThreemaGateway_Constants::TfaBasePriority - 15);
        $providerInstaller['reversed'] = new ThreemaGateway_Installer_TfaProvider(
            ThreemaGateway_Constants::TfaIDprefix . '_reversed',
            'ThreemaGateway_Tfa_Reversed',
            ThreemaGateway_Constants::TfaBasePriority - 10);

        return $providerInstaller;
    }

    /**
     * At the installation this will check the XenForo version and
     * remove the 2FA provider to the table.
     *
     * @param  string $error Will be filled by a human-readable error description
     *                       when an error occurs
     * @return bool
     */
    protected static function meetsRequirements(&$error)
    {
        /** @var bool $isError whether an error is triggered */
        $isError = false;

        // check XenForo version
        if (XenForo_Application::$versionId < 1050051) {
            $error .= 'This add-on requires XenForo 1.5.0 or higher.' . PHP_EOL;
            $isError = true;
        }

        // check PHP version
        if (version_compare(PHP_VERSION, '5.4', '<')) {
            $error .= 'Threema Gateway requires PHP version 5.4 or higher. Current version: ' . PHP_VERSION . PHP_EOL;
            $isError = true;
        }

        // check MySql version
        if (mysqli_get_server_version() < 50503) { //require v5.5.3
            $error .= 'Threema Gateway requires MySQL version 5.5.3 or higher. Current version: ' . mysqli_get_server_info() . PHP_EOL;
            $isError = true;
        }

        return !$isError;
    }
}

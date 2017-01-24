<?php
/**
 * Status managment for Threema Gateway.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Option_Status
{
    /**
     * @var int Below this amount of credits a warning is shown.
     */
    const CREDITS_WARN = 100;

    /**
     * Renders the status "option".
     *
     * @param XenForo_View $view           View object
     * @param string       $fieldPrefix    Prefix for the HTML form field name
     * @param array        $preparedOption Prepared option info
     * @param bool         $canEdit        True if an "edit" link should appear
     *
     * @return XenForo_Template_Abstract Template object
     */
    public static function renderHtml(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
    {
        /** @var array $status */
        $status = ['libsodium', 'libsodiumphp', 'phpsdk', 'credits'];
        /** @var string $extraError */
        $extraError = '';

        /** @var bool $technSuccess */
        $technSuccess = self::checkTecRequire($status, $extraError);

        /** @var ThreemaGateway_Handler_Settings $gwSettings */
        $gwSettings = new ThreemaGateway_Handler_Settings;

        // only go on if technically everything is okay to prevent PHP errors when accessing the SDK
        if ($technSuccess) {
            /** @var bool $phpSdkSuccess */
            $phpSdkSuccess = self::checkPhpSdk($status, $extraError, $gwSettings);

            // Only continue if there are no errors in the PHP SDK.
            // We could try it anyway, but this would be useless as it certainly
            // fails anyway and if you have a broken setup, you also have bigger
            // issues than your credits count.
            if ($phpSdkSuccess) {
                self::queryCredits($status, $extraError);
            }
        }

        if ($gwSettings->isDebug()) {
            if (XenForo_Application::debugMode()) {
                $extraError[] = [
                    'text' => new XenForo_Phrase('option_threema_gateway_status_debug_mode_active'),
                    'descclass' => 'warning'
                ];
            } else {
                $extraError[] = [
                    'text' => new XenForo_Phrase('option_threema_gateway_status_debug_mode_potentially_active'),
                    'descclass' => 'warning'
                ];
            }
        }

        $editLink = $view->createTemplateObject('option_list_option_editlink', [
            'preparedOption' => $preparedOption,
            'canEditOptionDefinition' => $canEdit
        ]);

        return $view->createTemplateObject('threemagw_option_list_status', [
            'fieldPrefix' => $fieldPrefix,
            'listedFieldName' => $fieldPrefix . '_listed[]',
            'preparedOption' => $preparedOption,
            'editLink' => $editLink,
            'status' => $status,
            'additionalerror' => $extraError,
            'isConfError' => (!$technSuccess),
            'isPhpSdkError' => (!$phpSdkSuccess),
        ]);
    }

    /**
     * Checks whether all technical requirements of the add-on installation
     * are fullfilled.
     *
     * Mostly only checks for Libsodium and Libsodium-PHP.
     * Return false when a serious error happens, which indicates that the
     * requirements are not fullfilled.
     *
     * @param  array $status     Will be filled with required statuses (sic)
     * @param  array $extraError Optional other errors may be added here
     * @return bool
     */
    protected static function checkTecRequire(&$status, &$extraError)
    {
        // optional check: HTTPS
        if (!XenForo_Application::$secure) {
            $extraError[]['text'] = new XenForo_Phrase('option_threema_gateway_status_no_https');
        }

        //libsodium
        if (extension_loaded('libsodium')) {
            if (method_exists('Sodium', 'sodium_version_string')) {
                $status['libsodium']['text']      = new XenForo_Phrase('option_threema_gateway_status_libsodium_version', ['version' => Sodium::sodium_version_string()]);
                $status['libsodium']['descr']     = new XenForo_Phrase('option_threema_gateway_status_libsodium_outdated');
                $status['libsodium']['descclass'] = 'warning';
            } else {
                $status['libsodium']['text'] = new XenForo_Phrase('option_threema_gateway_status_libsodium_version', ['version' => \Sodium\version_string()]);
            }

            // & libsodium-php
            $status['libsodiumphp']['text'] = new XenForo_Phrase('option_threema_gateway_status_libsodiumphp_version', ['version' => phpversion('libsodium')]);
            if (version_compare(phpversion('libsodium'), '1.0.1', '<')) {
                $status['libsodiumphp']['descr']     = new XenForo_Phrase('option_threema_gateway_status_libsodiumphp_outdated');
                $status['libsodiumphp']['descclass'] = 'warning';
            }
        } else {
            $status['libsodium']['text'] = new XenForo_Phrase('option_threema_gateway_status_libsodium_not_installed');
            if (PHP_INT_SIZE < 8) {
                $status['libsodium']['descr']         = new XenForo_Phrase('option_threema_gateway_status_libsodium_not_installed_required_64bit');
                $status['libsodium']['descclass']     = 'error';
                return false;
            } else {
                $status['libsodium']['descr']     = new XenForo_Phrase('option_threema_gateway_status_libsodium_not_installed_recommend');
                $status['libsodium']['descclass'] = 'warning';
            }
        }

        // there may be warnings, but apart from that all is okay
        return true;
    }

    /**
     * Checks whether the add-on/PHP-SDK is correctly configured and this ready
     * to use.
     *
     * It also automatically includes status indicators from the SDK.
     * Return false when a serious error happens, which indicates that the
     * requirements are not fullfilled.
     *
     * @param  array                           $status     Will be filled with required statuses (sic)
     * @param  array                           $extraError Optional other errors may be added here
     * @param  ThreemaGateway_Handler_Settings $gwSettings
     * @return bool
     */
    protected static function checkPhpSdk(&$status, &$extraError, ThreemaGateway_Handler_Settings $gwSettings = null)
    {
        // auto-create Gateway settings if not given
        if ($gwSettings == null) {
            $gwSettings = new ThreemaGateway_Handler_Settings;
        }

        //show PHP SDK version, checks if PHP-SDK is correctly setup
        try {
            $sdk = ThreemaGateway_Handler_PhpSdk::getInstance($gwSettings);
            //Note: When the SDK throws an exception the two lines below cannot be executed, so the version number cannot be determinated
            $status['phpsdk']['text']     = new XenForo_Phrase('option_threema_gateway_status_phpsdk_version', ['version' => $sdk->getVersion()]);
            $status['phpsdk']['addition'] = new XenForo_Phrase('option_threema_gateway_status_phpsdk_featurelevel', ['level' => $sdk->getFeatureLevel()]);
        } catch (Exception $e) {
            $extraError[]['text'] = new XenForo_Phrase('option_threema_gateway_status_custom_phpsdk_error') . $e->getMessage();
            return false;
        }

        // check whether Gateway is ready to use
        if (!$gwSettings->isReady()) {
            // If SDK is not ready, check whether it is at least available
            if ($gwSettings->isAvaliable()) {
                // presumambly an error in setup
                $extraError[]['text'] = new XenForo_Phrase('option_threema_gateway_status_phpsdk_not_ready');
            } else {
                // presumambly not yet setup (default settings or so)
                $extraError[] = [
                    'text' => new XenForo_Phrase('option_threema_gateway_status_phpsdk_not_ready_yet'),
                    'descclass' => 'warning'
                ];
            }

            return false;
        }

        // there may be warnings, but apart from that all is okay
        return true;
    }

    /**
     * When the user is allowed to view the credits, this queries them and adds
     * the result as a status message.
     *
     * Return false when the permissions could not be fetched.
     * When the user has not enough permissions to do so, this method returns
     * true anyway.
     *
     * @param  array $status     Will be filled with required statuses (sic)
     * @param  array $extraError Optional other errors may be added here
     * @return bool
     */
    protected static function queryCredits(&$status, &$extraError)
    {
        /** @var ThreemaGateway_Handler_Permissions $permissions */
        $permissions = ThreemaGateway_Handler_Permissions::getInstance();

        // check permissions for accessing credits
        if (!$permissions->hasPermission('credits')) {
            $status['credits']['text']      = new XenForo_Phrase('option_threema_gateway_status_credits', ['credits' => 'No permission']);
            $status['credits']['descr']     = new XenForo_Phrase('option_threema_gateway_status_credits_permission');
            $status['credits']['descclass'] = 'warning';

            return true;
        }

        // always there credit text
        $status['credits']['addition'] = new XenForo_Phrase('option_threema_gateway_status_credits_recharge');

        // try to fetch credits
        try {
            $gwServer = new ThreemaGateway_Handler_Action_GatewayServer;
            /** @var int|string $credits */
            $credits = $gwServer->getCredits();
        } catch (Exception $e) {
            // special error handling
            $extraError[]['text'] = new XenForo_Phrase('option_threema_gateway_status_custom_gwserver_error') . $e->getMessage();
            $credits              = 'N/A';

            // add (general) status error
            $status['credits']['descr']     = new XenForo_Phrase('option_threema_gateway_status_credits_error');
            $status['credits']['descclass'] = 'error';

            // wanna use finally block here, but is only available in PHP >= 5.5 :(
            // so duplicate code…
            $status['credits']['text'] = new XenForo_Phrase('option_threema_gateway_status_credits', ['credits' => $credits]);
            return false;
        }

        // when no error happens, check whether credits are "enough"
        $status['credits']['text'] = new XenForo_Phrase('option_threema_gateway_status_credits', ['credits' => $credits]);
        if ($credits == 0) {
            $status['credits']['descr']     = new XenForo_Phrase('option_threema_gateway_status_credits_out');
            $status['credits']['descclass'] = 'error';
            return false;
        } elseif ($credits < self::CREDITS_WARN) {
            $status['credits']['descr']     = new XenForo_Phrase('option_threema_gateway_status_credits_low');
            $status['credits']['descclass'] = 'warning';
        }

        // there may be warnings, but apart from that all is okay
        return true;
    }
}

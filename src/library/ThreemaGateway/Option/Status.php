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
        /** @var bool */
        $isConfError     = false;
        /** @var bool */
        $isPhpSdkError   = false;
        /** @var array */
        $status          = ['libsodium', 'libsodiumphp', 'phpsdk', 'credits'];
        /** @var string */
        $additionalerrors = '';

        //get XenForo required things
        /** @var XenForo_Visitor */
        $visitor = XenForo_Visitor::getInstance();

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
                $isConfError                          = true;
                $status['libsodium']['descr']         = new XenForo_Phrase('option_threema_gateway_status_libsodium_not_installed_required_64bit');
                $status['libsodium']['descclass']     = 'error';
            } else {
                $status['libsodium']['descr']     = new XenForo_Phrase('option_threema_gateway_status_libsodium_not_installed_recommend');
                $status['libsodium']['descclass'] = 'warning';
            }
        }

        //only go on if all checked requirements are okay to prevent PHP errors when accessing the SDK
        $gwSettings = new ThreemaGateway_Handler_Settings;
        if (!$isConfError) {
            $permissions = ThreemaGateway_Handler_Permissions::getInstance();

            //show PHP SDK version
            try {
                $sdk = ThreemaGateway_Handler_PhpSdk::getInstance($gwSettings);
                //Note: When the SDK throws an exception the two lines below cannot be executed, so the version number cannot be determinated
                $status['phpsdk']['text'] = new XenForo_Phrase('option_threema_gateway_status_phpsdk_version', ['version' => $sdk->getVersion()]);
                $status['phpsdk']['addition'] = new XenForo_Phrase('option_threema_gateway_status_phpsdk_featurelevel', ['level' => $sdk->getFeatureLevel()]);
            } catch (Exception $e) {
                $additionalerrors[]['text'] = new XenForo_Phrase('option_threema_gateway_status_custom_phpsdk_error').$e->getMessage();
                $isPhpSdkError = true;
            }

            // check permissions
            if (!$permissions->hasPermission('credits')) {
                $status['credits']['text']      = new XenForo_Phrase('option_threema_gateway_status_credits', ['credits' => 'No permission']);
                $status['credits']['descr']     = new XenForo_Phrase('option_threema_gateway_status_credits_permission');
                $status['credits']['descclass'] = 'warning';
            } elseif ($gwSettings->isReady()) {
                // if available show credits
                try {
                    $gwServer = new ThreemaGateway_Handler_Action_GatewayServer;
                    $credits = $gwServer->getCredits();
                } catch (Exception $e) {
                    if (!$isPhpSdkError) {
                        // only add error if it really includes some useful information
                        // if the SDK already has an error it is clear that this will
                        // also fail. Mostly it just fails with "Undefined variable:
                        // cryptTool".
                        $additionalerrors[]['text'] = new XenForo_Phrase('option_threema_gateway_status_custom_gwserver_error').$e->getMessage();
                    }
                    $credits = 'N/A';
                }

                $status['credits']['text'] = new XenForo_Phrase('option_threema_gateway_status_credits', ['credits' => $credits]);
                if ($credits == 'N/A') {
                    $status['credits']['descr']     = new XenForo_Phrase('option_threema_gateway_status_credits_error');
                    $status['credits']['descclass'] = 'error';
                } elseif ($credits == 0) {
                    $status['credits']['descr']     = new XenForo_Phrase('option_threema_gateway_status_credits_out');
                    $status['credits']['descclass'] = 'error';
                } elseif ($credits < 50) {
                    $status['credits']['descr']     = new XenForo_Phrase('option_threema_gateway_status_credits_low');
                    $status['credits']['descclass'] = 'warning';
                }

                $status['credits']['addition'] = new XenForo_Phrase('option_threema_gateway_status_credits_recharge');
            } else {
                // SDK not ready
                if ($gwSettings->isAvaliable()) {
                    // presumambly an error in setup
                    $additionalerrors[]['text'] = new XenForo_Phrase('option_threema_gateway_status_phpsdk_not_ready');
                } else {
                    // presumambly not yet setup (default settings or so)
                    $additionalerrors[] = [
                        'text' => new XenForo_Phrase('option_threema_gateway_status_phpsdk_not_ready_yet'),
                        'descclass' => 'warning'
                    ];
                }
            }
        }

        /* @var XenForo_Options */
        $options = XenForo_Application::getOptions();

        if ($options->threema_gateway_logreceivedmsgs['enabled']) {
            if (XenForo_Application::debugMode()) {
                $additionalerrors[] = [
                    'text' => new XenForo_Phrase('option_threema_gateway_status_debug_mode_active'),
                    'descclass' => 'warning'
                ];
            } else {
                $additionalerrors[] = [
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
            'additionalerror' => $additionalerrors,
            'isConfError' => $isConfError,
            'isPhpSdkError' => $isPhpSdkError,
        ]);
    }
}

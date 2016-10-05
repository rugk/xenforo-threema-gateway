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
        $isConfError     = false;
        $status          = ['libsodium', 'libsodiumphp', 'phpsdk', 'credits'];
        $additionalerror = '';

        //get old options
        $visitor = XenForo_Visitor::getInstance();
        $options = XenForo_Application::getOptions();

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
        if (!$isConfError) {
            //show PHP SDK version
            $handler                  = ThreemaGateway_Handler::getInstance();
            $handlerServer            = new ThreemaGateway_Handler_GatewayServer;
            $status['phpsdk']['text'] = new XenForo_Phrase('option_threema_gateway_status_phpsdk_version', ['version' => $handler->SdkVersion]);
            $status['phpsdk']['addition'] = new XenForo_Phrase('option_threema_gateway_status_phpsdk_featurelevel', ['level' => $handler->SdkFeatureLevel]);

            // check permissions
            if (!$handler->hasPermission('credits')) {
                $status['credits']['text']      = new XenForo_Phrase('option_threema_gateway_status_credits', ['credits' => 'No permission']);
                $status['credits']['descr']     = new XenForo_Phrase('option_threema_gateway_status_credits_permission');
                $status['credits']['descclass'] = 'warning';
            } elseif ($handler->isAvaliable()) {
                // if available show credits
                try {
                    $credits = $handlerServer->getCredits();
                } catch (Exception $e) {
                    // TODO: show error message instead of discarding it, helps for debugging
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

                if (!$handler->isReady()) {
                    $additionalerror = new XenForo_Phrase('option_threema_gateway_status_missing_private_key');
                }
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
            'additionalerror' => $additionalerror,
            'isConfError' => $isConfError,
        ]);
    }
}

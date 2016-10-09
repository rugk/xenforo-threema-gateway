<?php
/**
 * Private key path option.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

class ThreemaGateway_Option_DebugModeLog
{
    /**
     * @var string Default file path
     */
    const DefaultPath = 'internal_data/threemagateway/receivedmsgs.log';

    /**
     * Renders the debug mode log setting.
     *
     * Basically it just hides the setting if the debug mode of XenFOro is disabled.
     *
     * @param XenForo_View $view           View object
     * @param string       $fieldPrefix    Prefix for the HTML form field name
     * @param array        $preparedOption Prepared option info
     * @param bool         $canEdit        True if an "edit" link should appear
     *
     * @return XenForo_Template_Abstract Template object
     */
    public static function renderOption(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
    {
        $preparedOption['option_value'] = self::correctOption($preparedOption['option_value']);

        $gwSettings = new ThreemaGateway_Handler_Settings();

        // hide option when disabled and debug mode is off (so that users are not confused)
        if (!$gwSettings->isDebug()) {
            return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal('threemagateway_option_list_option_hidden', $view, $fieldPrefix, $preparedOption, $canEdit);
        }

        // set options
        $preparedOption['edit_format']  = 'onofftextbox';
        $preparedOption['formatParams'] = [
            'onoff' => 'enabled',
            'value' => 'path',
            'type' => 'textbox',
            'default' => self::DefaultPath,
            'placeholder' => self::DefaultPath
        ];

        //pass this to the default handler
        return XenForo_ViewAdmin_Helper_Option::renderPreparedOptionHtml($view, $preparedOption, $canEdit);
    }

    /**
     * Verifies whether the dir of the file is valid (can be created) and is writable.
     *
     * @param string             $filepath  Input
     * @param XenForo_DataWriter $dw
     * @param string             $fieldName Name of field/option
     *
     * @return bool
     */
    public static function verifyOption(&$filepath, XenForo_DataWriter $dw, $fieldName)
    {
        $filepath = self::correctOption($filepath);

        // check path
        $dirpath     = dirname($filepath['path']);
        $absoluteDir = XenForo_Application::getInstance()->getRootDir() . '/' . $dirpath;
        if (!ThreemaGateway_Handler_Validation::checkDir($absoluteDir)) {
            $dw->error(new XenForo_Phrase('threemagw_invalid_debuglogpath'), $fieldName);
            return false;
        }

        // auto-remove existing file if disabled
        if (!$filepath['enabled'] && file_exists($filepath['path'])) {
            unlink(realpath($filepath['path']));
        }

        return true;
    }

    /**
     * Corrects the option array.
     *
     * @param string $option
     * @return string
     */
    protected static function correctOption($option)
    {
        // correct value
        if (empty($option)) {
            /* @var XenForo_Options */
            $xenOptions = XenForo_Application::getOptions();

            // save file path even if disabled
            $option['enabled'] = 0;
            $option['path']    = $xenOptions->threema_gateway_logreceivedmsgs['path'];
        }

        // set default value
        if (empty($option['path'])) {
            $option['path'] = self::DefaultPath;
        }

        // correct path
        if (substr($option['path'], 0, 1) == '/') {
            $option['path'] = substr($option['path'], 1);
        }

        return $option;
    }
}

<?php
/**
 * Allow get for receiving messages option.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

class ThreemaGateway_Option_AllowGet
{
    /**
     * Renders the allow get debug setting.
     *
     * Basically it just hides the setting if the debug mode of XenForo is disabled.
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
        // hide option when disabled and debug mode is off (so that users are not confused)
        if (!XenForo_Application::debugMode() && !$preparedOption['option_value']) {
            return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal('threemagateway_option_list_option_hidden', $view, $fieldPrefix, $preparedOption, $canEdit);
        }

        // set options
        $preparedOption['edit_format']  = 'onoff';
        $preparedOption['formatParams'] = [];

        //pass this to the default handler
        return XenForo_ViewAdmin_Helper_Option::renderPreparedOptionHtml($view, $preparedOption, $canEdit);
    }
}

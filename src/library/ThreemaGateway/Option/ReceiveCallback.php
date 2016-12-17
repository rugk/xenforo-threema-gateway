<?php
/**
 * Receive callback option/display for admins to copy link.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

class ThreemaGateway_Option_ReceiveCallback
{
    /**
     * @var int The default length of an access token
     */
    const ACCESS_TOKEN_LENGTH = 46;

    /**
     * Renders the debug mode text input field with on/off buttons.
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
        /** @var XenForo_Options $options */
        $options = XenForo_Application::getOptions();

        // set default value
        if (empty($preparedOption['option_value'])) {
            $preparedOption['option_value'] = self::generateDefault();
        }

        //modify array to use custom template
        $preparedOption['edit_format']  = 'template';
        $preparedOption['formatParams'] = [
            'template' => 'threemagw_option_list_receivecallback',
            'basetext' => $options->boardUrl . '/' . ThreemaGateway_Constants::CALLBACK_FILE . '?accesstoken=',
            'placeholder' => ''
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
    public static function verifyOption(&$input, XenForo_DataWriter $dw, $fieldName)
    {
        // set default value
        if (empty($input)) {
            $input = self::generateDefault();
        }

        return true;
    }

    /**
     * Generates the default, which should be used for this option.
     *
     * @return string
     */
    protected static function generateDefault()
    {
        return ThreemaGateway_Helper_Random::getRandomAlphaNum(self::ACCESS_TOKEN_LENGTH);
    }
}

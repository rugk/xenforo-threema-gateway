<?php
/**
 * Threema Gateway ID option.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

class ThreemaGateway_Option_ThreemaGatewaySecret
{
    /**
     * @var int Chars not to censor when displaying secret
     */
    const CHARS_LEAVE_UNCENSORED = 2;

    /**
     * Renders the Threema Gateway Secret text input field.
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
        if ($preparedOption['option_value']) {
            //censor option
            $preparedOption['option_value'] = ThreemaGateway_Helper_General::censorString($preparedOption['option_value'], self::CHARS_LEAVE_UNCENSORED);

            // add note to explain *** in field
            $preparedOption['explain']->setParams([
                'note' => new XenForo_Phrase('option_threema_gateway_threema_id_secret_explain_note')
            ]);
        } else {
            // set note to empty string
            $preparedOption['explain']->setParams([
                'note' => ''
            ]);
        }

        //modify array to show text box
        $preparedOption['edit_format']  = 'textbox';
        $preparedOption['formatParams'] = ['placeholder' => ''];

        //pass this to the default handler
        return XenForo_ViewAdmin_Helper_Option::renderPreparedOptionHtml($view, $preparedOption, $canEdit);
    }

    /**
     * Verifies the Threema Gateway Secret format.
     *
     * @param string             $threemaid  Input threema ID
     * @param XenForo_DataWriter $dataWriter
     * @param string             $fieldName  Name of field/option
     *
     * @return bool
     */
    public static function verifyOption(&$threemagwsecret, XenForo_DataWriter $dataWriter, $fieldName)
    {
        //check whether change was really done by user
        // https://regex101.com/r/eY2uE3/3
        if (preg_match('/\*{' . (16 - self::CHARS_LEAVE_UNCENSORED) . '}[A-Za-z0-9]*/', $threemagwsecret)) {
            $threemagwsecret = $dataWriter->getExisting('option_value'); //reset old value
            return true;
        }

        //check for formal errors
        if ($threemagwsecret != '' && !preg_match('/[A-Za-z0-9]{16}/', $threemagwsecret)) {
            $dataWriter->error(new XenForo_Phrase('threemagw_invalid_threema_secret'), $fieldName);
            return false;
        }

        return true;
    }
}

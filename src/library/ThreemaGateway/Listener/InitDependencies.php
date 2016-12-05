<?php
/**
 * Extent init_dependencies with template helpers.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Listener_InitDependencies
{
    /**
     * Extent init_dependencies with template helpers.
     *
     * @param XenForo_Dependencies_Abstract $dependencies
     * @param array                         $data
     */
    public static function addHelpers(XenForo_Dependencies_Abstract $dependencies, array $data)
    {
        //check not necessary as we use an event hint
        XenForo_Template_Helper_Core::$helperCallbacks += [
            'threemaregex' => ['ThreemaGateway_Helper_General', 'threemaRegEx'],
            'threemaidverify' => ['ThreemaGateway_Helper_General', 'isThreemaId'],
            'threemagwcensor' => ['ThreemaGateway_Helper_General', 'censor'],
            'threemaidpubkey' => ['ThreemaGateway_Helper_Key', 'getPublic'],
            'threemaidpubkeyshort' => ['ThreemaGateway_Helper_Key', 'getPublicShort'],
            'threemashortpubkey' => ['ThreemaGateway_Helper_Key', 'getUserDisplay'],
            'threemaisvalidkey' => ['ThreemaGateway_Helper_Key', 'check'],
            'threemaisvalidpubkey' => ['ThreemaGateway_Helper_Key', 'checkPublic'],
            'threemakeyremovesuffix' => ['ThreemaGateway_Helper_Key', 'removeSuffix'],
            'emojiparseunicode' => ['ThreemaGateway_Helper_Emoji', 'parseUnicode'],
            'emojireplacedigits' => ['ThreemaGateway_Helper_Emoji', 'replaceDigits'],
        ];
    }
}

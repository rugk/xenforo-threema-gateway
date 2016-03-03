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
        //check not neccessary as we use an event hint
        XenForo_Template_Helper_Core::$helperCallbacks += [
            'threemaregex' => ['ThreemaGateway_Helper_General', 'regEx'],
            'threemaidverify' => ['ThreemaGateway_Helper_General', 'isThreemaId'],
            'threemaidpubkey' => ['ThreemaGateway_Helper_PublicKey', 'get'],
            'threemaidpubkeyshort' => ['ThreemaGateway_Helper_PublicKey', 'getShort'],
            'threemashortpubkey' => ['ThreemaGateway_Helper_PublicKey', 'convertShort'],
            'threemaispubkey' => ['ThreemaGateway_Helper_PublicKey', 'check'],
            'threemapubkeyremovesuffix' => ['ThreemaGateway_Helper_PublicKey', 'removeSuffix'],
            'threemagwcensor' => ['ThreemaGateway_Helper_General', 'censor'],
        ];
    }
}

<?php
/**
 * Show verification dialog after provider data changed.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

// TODO: Use XFCP system here!(?)
class ThreemaGateway_ViewPublic_TfaManage extends XenForo_ViewPublic_Base
{
    public function renderHtml()
    {
        /** @var XenForo_Tfa_AbstractProvider $provider */
        $provider = $this->_params['provider'];

        if ($this->_params['showSetup']) {
            $this->_params['newProviderHtml'] = $provider->renderVerification(
                $this, 'setup', $this->_params['user'], $this->_params['newProviderData'], $this->_params['newTriggerData']
            );
        } else {
            $this->_params['newProviderHtml'] = '';
        }
    }
}

<?php
/**
 * Passes data to Tfa model.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

class ThreemaGateway_ControllerAdmin_Login extends XFCP_ThreemaGateway_ControllerAdmin_Login
{
    /**
     * Passes the provider ID to the TFA model to enable customized behaviour.
     *
     * @return XenForo_ControllerResponse_View
     */
    public function actionTwoStep()
    {
        /** @var string $providerId */
        $providerId = $this->_input->filterSingle('provider', XenForo_Input::STRING);

        /** @var bool $isThreemaGwProvider whether this is a provider handled by us */
        $isThreemaGwProvider = in_array($providerId, ThreemaGateway_Constants::TFA_PROVIDER_ARRAY);

        if ($isThreemaGwProvider) {
            $this->_getTfaModel()->threemagwSetProviderId($providerId);
        } else {
            // to be sure, better reset the value if we do not handle the things
            // (when caching or so might retain this model)
            $this->_getTfaModel()->threemagwSetProviderId(null);
        }

        /** @var XenForo_ControllerResponse_View $parent original response */
        $parent = parent::actionTwoStep();

        return $parent;
    }

    /**
     * @return XenForo_Model_Tfa
     */
    protected function _getTfaModel()
    {
        return $this->getModelFromCache('XenForo_Model_Tfa');
    }
}

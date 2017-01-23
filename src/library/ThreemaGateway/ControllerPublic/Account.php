<?php
/**
 * Workaround for two step managment.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_ControllerPublic_Account extends XFCP_ThreemaGateway_ControllerPublic_Account
{
    /**
     * Expand XenForo's two factor enable managment as it is not properly implemented.
     *
     * @see https://xenforo.com/community/threads/1-5-documentation-for-two-step-authentication.102846/#post-1031047
     * @return XenForo_ControllerResponse_View
     */
    public function actionTwoStepEnable()
    {
        /** @var XenForo_ControllerResponse_View $parent */
        $parent = parent::actionTwoStepEnable();

        if ($parent instanceof XenForo_ControllerResponse_View) {
            // read params
            /** @var array $params */
            $params = $parent->subView->params;
            /** @var XenForo_Tfa_AbstractProvider $provider */
            $provider = $params['provider'];
            /** @var string $providerId */
            $providerId = $params['providerId'];
            /** @var array $providerData */
            $providerData = [];
            if (array_key_exists('providerData', $params)) {
                $providerData = $params['providerData'];
            }

            if (in_array($providerId, ThreemaGateway_Constants::TFA_PROVIDER_ARRAY)) {
                //get additional data
                /** @var XenForo_Visitor $visitor */
                $visitor = XenForo_Visitor::getInstance();

                //forward request to manager
                /** @var XenForo_ControllerResponse|null $result */
                $result = $provider->handleManage($this, $visitor->toArray(), $providerData);

                if (!$result) {
                    $result = $this->responseRedirect(
                        XenForo_ControllerResponse_Redirect::SUCCESS,
                        XenForo_Link::buildPublicLink('account/two-step')
                    );
                } elseif ($result instanceof XenForo_ControllerResponse_View) {
                    $result = $this->_getWrapper('account', 'two-step', $result);
                }

                return $result;
            }
        }

        return $parent;
    }
}

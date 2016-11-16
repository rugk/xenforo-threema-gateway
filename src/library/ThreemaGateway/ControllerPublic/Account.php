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
     * @var array PROVIDER_ARRAY list of providers handled
     */
    const PROVIDER_ARRAY = [
        'threemagw_conventional',
        'threemagw_reversed'
    ];

    /**
     * Expand XenForos two factor enable managment as it is not properly implemented.
     *
     * https://xenforo.com/community/threads/1-5-documentation-for-two-step-authentication.102846/#post-1031047
     *
     * @return XenForo_ControllerResponse_View
     */
    public function actionTwoStepEnable()
    {
        $parent = parent::actionTwoStepEnable();

        if ($parent instanceof XenForo_ControllerResponse_View) {
            // read params
            $params       = $parent->subView->params;
            $provider     = $params['provider'];
            $providerId   = $params['providerId'];
            $user         = $params['user'];
            $providerData = [];
            if (array_key_exists('providerData', $params)) {
                $providerData = $params['providerData'];
            }

            if (in_array($providerId, self::PROVIDER_ARRAY)) {
                //get additional data
                $visitor = XenForo_Visitor::getInstance();

                //forward request to manager
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

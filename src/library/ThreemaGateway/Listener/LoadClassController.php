<?php
/**
 * Use XFCP to extend controller classes.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Listener_LoadClassController
{
    /**
     * Extent XenForo's controllers.
     *
     * @param string $class
     * @param array  $extend
     */
    public static function extendAccountController($class, array &$extend)
    {
        switch ($class) {
            case 'XenForo_ControllerPublic_Account':
                $extend[] = 'ThreemaGateway_ControllerPublic_Account';
                break;

            case 'XenForo_ControllerPublic_Login':
                $extend[] = 'ThreemaGateway_ControllerPublic_Login';
                break;

            case 'XenForo_ControllerAdmin_Login':
                $extend[] = 'ThreemaGateway_ControllerAdmin_Login';
                break;
        }
    }
}

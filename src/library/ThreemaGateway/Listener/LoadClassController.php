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
     * Extent XenForo_ControllerPublic_Account with ThreemaGateway_ControllerPublic_Account.
     *
     * @param string $class
     * @param array  $extend
     */
    public static function extendAccountController($class, array &$extend)
    {
        //check not really necessary as we use an event hint, but just to be sure...
        if ($class == 'XenForo_ControllerPublic_Account') {
            $extend[] = 'ThreemaGateway_ControllerPublic_Account';
        }
    }
}

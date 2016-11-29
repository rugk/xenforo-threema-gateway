<?php
/**
 * Use XFCP to extend classes.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Listener_LoadClass
{
    /**
     * Extend XenForo_Session with ThreemaGateway_Session.
     *
     * @param string $class
     * @param array  $extend
     */
    public static function extendClass($class, array &$extend)
    {
        //check not really neccessary as we use an event hint, but just to be sure...
        if ($class == 'XenForo_Session') {
            $extend[] = 'ThreemaGateway_Session';
        }
    }
}

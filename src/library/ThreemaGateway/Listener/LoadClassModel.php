<?php
/**
 * Use XFCP to extend classes.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Listener_LoadClassModel
{
    /**
     * Extend XenForo_Model_Tfa with ThreemaGateway_Model_Tfa.
     *
     * @param string $class
     * @param array  $extend
     */
    public static function extendClass($class, array &$extend)
    {
        //check not really necessary as we use an event hint, but just to be sure...
        if ($class == 'XenForo_Model_Tfa') {
            $extend[] = 'ThreemaGateway_Model_Tfa';
        }
    }
}

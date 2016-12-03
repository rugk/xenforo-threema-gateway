<?php
/**
 * Session extending to add possibility to skip IP address verification.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */


class ThreemaGateway_Session extends XFCP_ThreemaGateway_Session
{
    /**
     * Exposes the protected method _setup publicy, so everyone can use it.
     *
     * To skip IP address validation now one just have to use $ipAddress = false.
     *
     * @param string       $sessionId      Session ID to look up, if one exists
     * @param string|false $ipAddress      IP address in binary format or false, for access limiting.
     * @param array|null   $defaultSession If no session can be found, uses this as the default session value
     */
    public function threemagwSetupRaw($sessionId = '', $ipAddress = false, array $defaultSession = null)
    {
        return $this->_setup($sessionId, $ipAddress, $defaultSession);
    }
}

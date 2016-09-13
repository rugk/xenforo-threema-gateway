<?php
/**
 * Responsible for allowing applications to get received messages.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Handler_Receiver
{
    /**
     * @var ThreemaGateway_Handler $mainHandler
     */
    protected $mainHandler;

    /**
     * Startup.
     *
     */
    public function __construct()
    {
        $this->mainHandler = ThreemaGateway_Handler::getInstance();
    }

    /**
     * Check whether a specific message was received and returns it.
     *
     * @param  string            $senderId
     * @param  string            $message
     *
     * @throws XenForo_Exception
     * @return ???
     */
    public function getMessage($senderId, $keyword)
    {
        // check permission
        if (!$this->mainHandler->hasPermission('receive')) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_permission_error'));
        }
    }
}

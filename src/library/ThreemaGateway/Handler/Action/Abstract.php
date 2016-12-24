<?php
/**
 * General purpose action handler.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

abstract class ThreemaGateway_Handler_Action_Abstract
{
    /**
     * Gateway PHP SDK store.
     *
     * This variable is private as {@link getSdk()} should be used. This makes
     * sure the SDK is only initialized when it is really needed.
     *
     * @var ThreemaGateway_Handler_PhpSdk
     */
    private $sdk = null;

    /**
     * @var ThreemaGateway_Handler_Settings
     */
    protected $settings;

    /**
     * @var ThreemaGateway_Handler_Permissions
     */
    protected $permissions;

    /**
     * Startup.
     */
    public function __construct()
    {
        $this->settings    = new ThreemaGateway_Handler_Settings;
        $this->permissions = ThreemaGateway_Handler_Permissions::getInstance();
    }

    /**
     * Returns the PHP SDK object.
     *
     * This is meant for lazy loading the SDK, so it is only loaded when it is
     * actually accessed.
     *
     * @return ThreemaGateway_Handler_PhpSdk
     */
    protected function getSdk()
    {
        if ($this->sdk === null) {
            $this->sdk = ThreemaGateway_Handler_PhpSdk::getInstance($this->settings);
        }

        return $this->sdk;
    }

    /**
     * Returns the PHP SDK E2EHelper.
     *
     * @return Threema\MsgApi\Helpers\E2EHelper
     */
    protected function getE2EHelper()
    {
        return $this->getSdk()->getE2EHelper();
    }

    /**
     * Returns the Threema Receiver
     *
     * Note that you may need to call $this->getSdk(); manually if you want to
     * pass a specific type via the $type param.
     *
     * @param string $value the query value (Threema ID, phone number, …)
     * @param string $type the type of the queried data (use constzants of Threema\MsgApi\Receiver)
     *
     * @return Threema\MsgApi\Receiver
     */
    protected function getThreemaReceiver($value, $type = null)
    {
        // make sure the SDK is loaded
        $this->getSdk();

        if ($type === null) {
            $type = Threema\MsgApi\Receiver::TYPE_ID;
        }

        return new Threema\MsgApi\Receiver($value, $type);
    }

    /**
     * Returns the PHP SDK crypt tool.
     *
     * @return Threema\MsgApi\Tools\CryptTool
     */
    protected function getCryptTool()
    {
        return $this->getSdk()->getCryptTool();
    }

    /**
     * Returns the PHP SDK connector.
     *
     * @return Threema\MsgApi\Connection
     */
    protected function getConnector()
    {
        return $this->getSdk()->getConnector();
    }
}

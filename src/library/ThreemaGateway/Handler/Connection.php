<?php
/**
 * Creates a connection to the SDK.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

use Threema\MsgApi\Connection;
use Threema\MsgApi\ConnectionSettings;

class ThreemaGateway_Handler_Connection
{
    /**
     * @var Threema\MsgApi\ConnectionSettings Connection settings;
     */
    protected $settings;

    /**
     * Create the connection to the PHP-SDK.
     *
     * @param string $GatewayId     Your own gateway ID
     * @param string $GatewaySecret Your own gateway secret
     */
    public function __construct($GatewayId, $GatewaySecret)
    {
        $this->settings = $this->createConnectionSettings($GatewayId, $GatewaySecret);
    }

    /**
     * Creates the connection.
     *
     * @param  Threema\MsgApi\PublicKeyStore $keystore A MsgAPI keystore.
     * @return Threema\MsgApi\Connection
     */
    public function create($keystore)
    {
        return new Connection($this->settings, $keystore);
    }

    /**
     * Creates connection settings.
     *
     * @param string $GatewayId     Your own gateway ID
     * @param string $GatewaySecret Your own gateway secret
     *
     * @return ConnectionSettings
     */
    protected function createConnectionSettings($GatewayId, $GatewaySecret)
    {
        /* @var XenForo_Options */
        $options = XenForo_Application::get('options');

        if ($options->threema_gateway_usehttps) {
            //create a connection with advanced options
            $settings = new ConnectionSettings(
                $GatewayId,
                $GatewaySecret,
                null,
                [
                    'forceHttps' => true,
                    'tlsVersion' => '1.2',
                    'tlsCipher' => 'ECDHE-RSA-AES128-GCM-SHA256'
                ]
            );
        } else {
            //create a connection with default options
            $settings = new ConnectionSettings(
                $GatewayId,
                $GatewaySecret
            );
        }

        return $settings;
    }
}

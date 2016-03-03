<?php
/**
 * Public key conversion.
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
     * @var Threema\MsgApi\PublicKeyStore Public keystore;
     */
    protected $keystore;

    /**
     * Create the connection to the PHP-SDK.
     *
     * @param string $GatewayId     Your own gateway ID
     * @param string $GatewaySecret Your own gateway secret
     */
    public function __construct($GatewayId, $GatewaySecret)
    {
        /** @var XenForo_Options */
        $options = XenForo_Application::get('options');

        //Create connection
        $this->keystore = new ThreemaGateway_Handler_Keystore($options);
        $this->settings = $this->createConnectionSettings($GatewayId, $GatewaySecret, $options->threema_gateway_usehttps);
    }

    /**
     * Creates the connection.
     *
     * @return Threema\MsgApi\Connection $connector
     */
    public function create()
    {
        return new Connection($this->settings, $this->keystore);
    }

    /**
     * Creates connection settings.
     *
     * @param string $GatewayId     Your own gateway ID
     * @param string $GatewaySecret Your own gateway secret
     * @param bool $useTlsOptions whether to use advanced options or not
     *
     * @return ConnectionSettings
     */
    protected function createConnectionSettings($GatewayId, $GatewaySecret, $useTlsOptions)
    {
        if ($useTlsOptions === true) {
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

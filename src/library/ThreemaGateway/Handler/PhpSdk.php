<?php
/**
 * Provides the connection to the PHP SDK.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

use Threema\MsgApi\Receiver;
use Threema\MsgApi\Helpers\E2EHelper;
use Threema\MsgApi\Connection;
use Threema\MsgApi\ConnectionSettings;

class ThreemaGateway_Handler_PhpSdk
{
    /**
     * @var Singleton
     */
    private static $instance = null;

    /**
     * @var string Path to Threema Gateway PHP SDK
     */
    protected $SdkDir = __DIR__ . '/../threema-msgapi-sdk-php';

    /**
     * @var ThreemaGateway_Handler_Settings
     */
    protected $settings;

    /**
     * @var string Version of Threema Gateway PHP SDK
     */
    protected $sdkVersion;

    /**
     * @var int Feature level of PHP SDK
     */
    protected $sdkFeatureLevel;

    /**
     * @var Threema\MsgApi\Tools\CryptTool
     */
    protected $cryptTool;

    /**
     * @var Threema\MsgApi\PublicKeyStore
     */
    protected $keystore;

    /**
     * @var Threema\MsgApi\Connection The connector to the PHP-SDK
     */
    protected $connector;

    /**
     * @var E2EHelper The Threema E2E helper, which is necessary when dealing
     *                with end-to-end-encrypted messages.
     */
    protected $e2eHelper;

    /**
     * Initiate PHP-SDK.
     *
     * @param ThreemaGateway_Handler_Settings
     * @throws XenForo_Exception
     */
    private function __construct($settings)
    {
        // get options
        $this->xenOptions = XenForo_Application::getOptions();
        if ($settings !== null) {
            $this->settings = $settings;
        } else {
            $this->settings = new ThreemaGateway_Handler_Settings;
        }

        // load libraries
        $this->loadLib();

        //create keystore
        $this->createKeystore();

        //create connection
        $this->createConnection();
    }

    /**
     * Prevent cloning for Singleton.
     */
    private function __clone()
    {
        // I smash clones!
    }

    /**
     * SDK startup as a Singleton.
     *
     * @param ThreemaGateway_Handler_Settings If you already used the settings
     *                                        you can pass them here, so the
     *                                        class can reuse them.
     * @throws XenForo_Exception
     * @return void
     */
    public static function getInstance($settings = null)
    {
        if (!isset(static::$instance)) {
            static::$instance = new static($settings);
        }

        return static::$instance;
    }

    /**
     * Returns the version of the PDP SDK.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->sdkVersion;
    }

    /**
     * Returns the feature level of the SDK.
     *
     * @return string
     */
    public function getFeatureLevel()
    {
        return $this->sdkFeatureLevel;
    }

    /**
     * Returns the connector to the Threema Gateway.
     *
     * @return Threema\MsgApi\Connection
     */
    public function getConnector()
    {
        return $this->connector;
    }

    /**
     * Returns the E2EHelper to the Threema Gateway.
     *
     * @throws XenForo_Exception
     * @return E2EHelper         The connector to the PHP-SDK
     */
    public function getE2EHelper()
    {
        if (!is_object($this->e2eHelper)) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_missing_e2e_helper'));
        }
        return $this->e2eHelper;
    }

    /**
     * Returns a Receiver for a Threema ID.
     *
     * @param string $threemaId
     *
     * @return Receiver
     */
    public function getReceiver($threemaId)
    {
        return new Receiver($threemaId, Receiver::TYPE_ID);
    }

    /**
     * Returns the crypt tool.
     *
     * @return Threema\MsgApi\Tools\CryptTool
     */
    public function getCryptTool()
    {
        return $this->cryptTool;
    }

    /**
     * Returns the settings used for the PHP SDK.
     *
     * @return ThreemaGateway_Handler_Settings
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Loads the PHP-SDK.
     *
     * @throws XenForo_Exception
     */
    protected function loadLib()
    {
        // use source option can force the use of the source code, but there is
        // also an automatic fallback to the source
        if (!$this->xenOptions->threema_gateway_usesource && file_exists($this->SdkDir . '/threema_msgapi.phar')) {
            // PHAR mode
            require_once $this->SdkDir . '/threema_msgapi.phar';
        } elseif (file_exists($this->SdkDir . '/source/bootstrap.php')) {
            // source mode
            $this->SdkDir = $this->SdkDir . '/source';
            require_once $this->SdkDir . '/bootstrap.php';
        } else {
            // error
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_missing_sdk'));
        }

        // Set (missing) properties.
        $this->sdkVersion      = MSGAPI_SDK_VERSION;
        $this->sdkFeatureLevel = MSGAPI_SDK_FEATURE_LEVEL;
        $this->cryptTool       = $cryptTool;
    }

    /**
     * Creates a keystore.
     *
     * @param
     */
    protected function createKeystore()
    {
        /** @var array $phpKeystore The setting for an optional PHP keystore */
        $phpKeystore = $this->xenOptions->threema_gateway_keystorefile;

        if (!$phpKeystore || !$phpKeystore['enabled']) {
            $keystore = new ThreemaGateway_Handler_DbKeystore();
        } else {
            $keystore = new Threema\MsgApi\PublicKeyStores\PhpFile(__DIR__ . '/../' . $phpKeystore['path']);
        }
        $this->keystore = $keystore;
    }

    /**
     * Creates a keystore.
     */
    protected function createConnection()
    {
        $connectionSettings = $this->createConnectionSettings(
            $this->settings->getId(),
            $this->settings->getSecret()
        );
        $this->connector = new Connection($connectionSettings, $this->keystore);

        //create E2E helper if E2E mode is used
        if ($this->settings->isEndToEnd()) {
            $this->e2eHelper = new E2EHelper(
                $this->cryptTool->hex2bin(ThreemaGateway_Helper_Key::removeSuffix($this->settings->getPrivateKey())),
                $this->connector
            );
        }
    }

    /**
     * Creates connection settings.
     *
     * @param string $GatewayId     Your own gateway ID
     * @param string $GatewaySecret Your own gateway secret
     *
     * @throws XenForoException
     * @return ConnectionSettings
     */
    protected function createConnectionSettings($GatewayId, $GatewaySecret)
    {
        /** @var null|ConnectionSettings $settings */
        $settings = null;
        if ($this->xenOptions->threema_gateway_httpshardening) {
            //create a connection with advanced options
            /** @var array $tlsSettings */
            $tlsSettings = [];
            switch ($this->xenOptions->threema_gateway_httpshardening) {
                case 1:
                    // only force TLS v1.2
                    $tlsSettings = [
                            'forceHttps' => true,
                            'tlsVersion' => '1.2'
                        ];
                    break;
                case 2:
                    // also force strong cipher
                    $tlsSettings = [
                            'forceHttps' => true,
                            'tlsVersion' => '1.2',
                            'tlsCipher' => 'ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384'
                        ];
                    break;
                default:
                    throw new XenForo_Exception(new XenForo_Phrase('threemagw_invalid_httpshardening_option'));
                    break;
            }

            $settings = new ConnectionSettings(
                $GatewayId,
                $GatewaySecret,
                null,
                $tlsSettings
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

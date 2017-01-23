<?php
/**
 * Uses the settings to provide some high-level functions.
 *
 * Please do not use this directly. Better use
 * {@link ThreemaGateway_Handler_PhpSdk->getSettings()}. If you want to use the
 * settings before initiating the SDK, you can use this class before, but please
 * pass an instance of it to {@link ThreemaGateway_Handler_PhpSdk} in this case.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Handler_Settings
{
    /**
     * @var XenForo_Options $xenOptions XenForo options
     */
    protected $xenOptions;

    /**
     * @var string $GatewayId Your own Threema Gateway ID
     */
    protected $GatewayId = '';

    /**
     * @var string $gatewaySecret Your own Threema Gateway secret
     */
    protected $gatewaySecret = '';

    /**
     * @var string $privateKey Your own private key
     */
    protected $privateKey = '';

    /**
     * @var string $privateKeyBase The unconverted private key from settings.
     */
    private $privateKeyBase = '';

    /**
     * @var string $publicKey the public key converted from the private key {@see $privateKey}
     */
    protected $publicKey = '';

    /**
     * Initiate settings.
     */
    public function __construct()
    {
        $this->xenOptions = XenForo_Application::getOptions();

        // get options (if not hard-coded)
        if (!$this->GatewayId) {
            $this->GatewayId = $this->xenOptions->threema_gateway_threema_id;
        }
        if (!$this->gatewaySecret) {
            $this->gatewaySecret = $this->xenOptions->threema_gateway_threema_id_secret;
        }
        if (!$this->privateKey) {
            if (!$this->privateKeyBase) {
                $this->privateKeyBase = $this->xenOptions->threema_gateway_privatekeyfile;
            }

            // vadility check & processing is later done when private key is actually requested
            // {@see convertPrivateKey()}
        }
    }

    /**
     * Checks whether the Gateway is basically set up.
     *
     * Note that this may not check all requirements (like installed libsodium
     * and so on).
     * In contrast to {@link isReady()} this only checks whether it is possible
     * to query the Threema Server for data, not whether sending/receiving
     * messages is actually possible.
     * This does not check any permissions! Use
     * {@link ThreemaGateway_Handler_Permissions->hasPermission()} for this
     * instead!
     *
     * @return bool
     */
    public function isAvaliable()
    {
        if (!$this->GatewayId ||
            !$this->gatewaySecret ||
            $this->xenOptions->threema_gateway_e2e == ''
        ) {
            return false;
        }

        return true;
    }

    /**
     * Checks whether everything is comple, so sending and receiving messages
     * is (theoretically) possible.
     *
     * This includes {@link isAvaliable()} as a basic check.
     * This does not check any permissions! Use
     * {@link ThreemaGateway_Handler_Permissions->hasPermission()} for this
     * instead!
     *
     * @return bool
     */
    public function isReady()
    {
        // basic check
        if (!$this->isAvaliable()) {
            return false;
        }

        //check whether sending and receiving is possible
        if ($this->isEndToEnd()) {
            // fast check
            if (!$this->privateKey && !$this->privateKeyBase) {
                return false;
            }

            // get private key if necessary
            if (!$this->privateKey) {
                try {
                    $this->convertPrivateKey();
                } catch (XenForo_Exception $e) {
                    // in case of an error, it is not ready
                    return false;
                }
            }

            // if the key is (still) invalid, return error
            if (!$this->isPrivateKey($this->privateKey)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks whether sending uses the end-to-end encrypted mode.
     *
     * Note: When E2E mode is not used it is also not possible to receive
     * messages.
     *
     * @return bool
     */
    public function isEndToEnd()
    {
        return ($this->xenOptions->threema_gateway_e2e == 'e2e');
    }

    /**
     * Checks whether the Gateway is running in debug mode.
     *
     * You may use this to show scary messages to the admins ;-) or to
     * conditionally disable functionality.
     *
     * @return bool
     */
    public function isDebug()
    {
        return (($this->xenOptions->threema_gateway_logreceivedmsgs['enabled'] ||
            $this->xenOptions->threema_gateway_allow_get_receive) &&
            XenForo_Application::debugMode());
    }

    /**
     * Returns the gateway ID.
     *
     * @return string
     */
    public function getId()
    {
        return $this->GatewayId;
    }

    /**
     * Returns the gateway secret.
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->gatewaySecret;
    }

    /**
     * Returns the private key.
     *
     * @return string
     */
    public function getPrivateKey()
    {
        if (!$this->privateKey) {
            $this->convertPrivateKey();
        }

        return $this->privateKey;
    }

    /**
     * Returns the public key.
     *
     * @return string
     */
    public function getOwnPublicKey()
    {
        if (!$this->publicKey) {
            /** @var ThreemaGateway_Handler_Action_KeyConverter $keyConverter */
            $keyConverter    = new ThreemaGateway_Handler_Action_KeyConverter;
            $this->publicKey = $keyConverter->derivePublicKey($this->getPrivateKey());
        }

        return $this->publicKey;
    }

    /**
     * Checks and processes the private key. Throws an exception if something
     * is wrong.
     *
     * @throws XenForo_Exception
     */
    protected function convertPrivateKey()
    {
        // find path of private key file
        if (file_exists(__DIR__ . '/../' . $this->privateKeyBase)) {
            /** @var resource|false $fileres */
            $fileres = fopen(__DIR__ . '/../' . $this->privateKeyBase, 'r');
        } elseif (ThreemaGateway_Helper_Key::check($this->privateKeyBase, 'private:')) {
            // use raw key (undocumented, not recommend)
            $this->privateKey = $this->privateKeyBase;
        } else {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_invalid_privatekey'));
        }

        // read content of private key file
        if (is_resource($fileres)) {
            $this->privateKey = fgets($fileres);
            fclose($fileres);
        } else {
            //error opening file
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_invalid_keystorepath'));
        }
    }

    /**
     * Checks whether the string actually is a private key.
     *
     * @param  string $privateKey The string to check.
     * @return bool
     */
    protected function isPrivateKey($privateKey)
    {
        return ThreemaGateway_Helper_Key::check($privateKey, 'private:');
    }

    /**
     * Convert object to string.
     *
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . ' of ' . $this->GatewayId;
    }
}

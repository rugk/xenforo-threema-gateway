<?php
/**
 * Gateway handler.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

use Threema\MsgApi\Receiver;
use Threema\MsgApi\Helpers\E2EHelper;

/**
 * This handler provides the connection of the Threema Gateway (SDK) to all
 * other parts of the add-on or third-party software.
 */
class ThreemaGateway_Handler
{
    /**
     * @var XenForo_Options $xenOptions XenForo options
     */
    protected $xenOptions;

    /**
     * @var string $GatewayId Your own Threema Gateway ID
     */
    private $GatewayId = '';

    /**
     * @var string $GatewaySecret Your own Threema Gateway Secret
     */
    private $GatewaySecret = '';

    /**
     * @var string $PrivateKey Your own private key
     */
    private $PrivateKey = '';

    /**
     * @var string Version of Threema Gateway PHP SDK
     */
    public $SdkVersion;

    /**
     * @var string Path to Threema Gateway PHP SDK
     */
    public $SdkDir = __DIR__ . '/threema-msgapi-sdk-php';

    /**
     * @var ThreemaGateway_Handler_Connection The connector to the PHP-SDK
     */
    protected $connector;

    /**
     * @var array|null User whose using the Threema Gateway
     */
    protected $user = null;

    /**
     * @var array Permissions cache
     */
    protected $permissions;

    /**
     * SDK startup.
     *
     * @param  array|null        $newUser (optional) User array (passed to
     *                                    {@link setUserId()})
     * @throws XenForo_Exception
     * @return void
     */
    public function __construct($user = null)
    {
        // optionally set user
        if ($user !== null) {
            $this->setUserId($user);
        };

        // get options
        /** @var XenForo_Options $options */
        $options          = XenForo_Application::getOptions();
        $this->xenOptions = $options;

        // evaluate options
        if (!$this->GatewayId) {
            $this->GatewayId = $options->threema_gateway_threema_id;
        }
        if (!$this->GatewaySecret) {
            $this->GatewaySecret = $options->threema_gateway_threema_id_secret;
        }
        if (!$this->PrivateKey) {
            /** @var string $filepath */
            $filepath   = $options->threema_gateway_privatekeyfile;

            if ($filepath) {
                // find path of private key file
                if (file_exists(__DIR__ . '/' . $filepath)) {
                    /** @var resource|false $fileres */
                    $fileres = fopen(__DIR__ . '/' . $filepath, 'r');
                } elseif (ThreemaGateway_Handler_Key::check($filepath, 'private:')) {
                    // use raw key (undocumented, not recommend)
                    $this->PrivateKey = $filepath;
                } else {
                    throw new XenForo_Exception(new XenForo_Phrase('threemagw_invalid_privatekey'));
                }

                // read content of private key file
                if (is_resource($fileres)) {
                    $this->PrivateKey = fgets($fileres);
                    fclose($fileres);
                } else {
                    //error opening file
                    throw new XenForo_Exception(new XenForo_Phrase('threemagw_invalid_keystorepath'));
                }
            }
        }

        // load libraries
        // use source option can force the use of the source code, but there is
        // also an automatic fallback to the source
        if (!$options->threema_gateway_usesource && file_exists($this->SdkDir . '/threema_msgapi.phar')) {
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
        $this->SdkVersion = MSGAPI_SDK_VERSION;

        //create connection
        $connectorHelper = new ThreemaGateway_Handler_Connection(
            $this->GatewayId,
            $this->GatewaySecret
        );
        /** @var ThreemaGateway_Handler_Connection $connector */
        $connector = $connectorHelper->create();

        $this->connector = $connector;
    }

    /**
     * Sets/Changes the user id of the user using the Threema Gateway. This
     * also forces a reload of the permission cache. (See {@link hasPermission()}).
     *
     * Returns true when value was changed. If false is returned the user is
     * already the visitor or the user id is already set or and the value was
     * not changed.
     *
     * @param  array|null $newUser (optional) User array
     * @return bool
     */
    public function setUserId($newUser = null)
    {
        // get user ids (or null)
        if ($this->user === null) {
            /** @var int|null $oldUserId User id of user (from class) */
            $oldUserId = null;
        } else {
            /** @var int|null $oldUserId User id of user (from class) */
            $oldUserId = $this->user['user_id'];
        }
        if ($newUser === null) {
            /** @var int|null $newUserId User id of new user (from param) */
            $newUserId = null;
        } else {
            /** @var int|null $newUserId User id of new user (from param) */
            $newUserId = $newUser['user_id'];
        }

        // check whether visitor is user
        /** @var bool $userIsAlreadyVisitor Whether the new user is already the default user and the
                        old user is the default user too.
        */
        $userIsAlreadyVisitor = $this->userIsDefault($newUser) && $this->userIsDefault($oldUserId);
        // prevent unneccessary changes
        if ($oldUserId == $newUserId || $userIsAlreadyVisitor) {
            return false;
        }

        //change user Id
        $this->user = $newUser;
        $this->hasPermission(null, true);
        return true;
    }

    /**
     * Checks whether the Gateway is basically set up.
     *
     * Note that this may not check all requirements (like installed libsodium
     * and so on). When the installation of this addon is broken you likely
     * already got an exception when initializing this class or you will get
     * one when sending a message or doing something similar.
     * In contrast to {@link isReady()} this only checks whether it is possible
     * to query the Threema Server for data, not whether sending/receiving
     * messages is actually possible.
     * This does not check any permissions! Use {@link hasPermission()} for
     * this instead!
     *
     * @return bool
     */
    public function isAvaliable()
    {
        if (!$this->GatewayId ||
            !$this->GatewaySecret ||
            $this->xenOptions->threema_gateway_e2e == ''
        ) {
            return false;
        }

        return true;
    }

    /**
     * Checks whether everything is completly sending and receiving messages
     * is (theoretically) possible.
     *
     * This includes {@link isAvaliable()} as a basic check.
     * This does not check any permissions! Use {@link hasPermission()} for
     * this instead!
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
            if (!$this->PrivateKey ||
                !ThreemaGateway_Handler_Key::check($this->PrivateKey, 'private:')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks whether sending uses end-to-end mode.
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
     * Checks whether the user has the permission to do something.
     *
     * This uses the user e.g. set by {@link setUserId()}. If no user is set it
     * uses the current visitor/user.
     * The currently avaliable actions are: use, send, receive, fetch, lookup,
     * tfa and credits
     * If you do not specify an action an array of all possible actions is
     * returned.
     * Note that "credits" is an admin permission and is therefore only avaliable
     * to admins.
     * However
     *
     * @param  string     $action  (optional) The action you want to do
     * @param  string     $noCache (optional) Forces the cache to reload
     * @return bool|array
     */
    public function hasPermission($action = null, $noCache = false)
    {
        /** @var array $permissions Temporary variable for permissions */
        $permissions = [];

        if ($this->user === null) {
            /** @var int|null $userId User id of user (from class) */
            $userId = null;
        } else {
            /** @var int|null $userId User id of user (from class) */
            $userId = $this->user['user_id'];
        }

        // get permissions and cache them
        if (!$noCache && is_array($this->permissions)) {
            // load from cache
            $permissions = $this->permissions;
        } else {
            if ($this->userIsDefault($userId)) {
                //normal visitor
                /** @var XenForo_Visitor $visitor */
                $visitor = XenForo_Visitor::getInstance();

                $permissions['use']     = $visitor->hasPermission('threemagw', 'use');
                $permissions['send']    = $visitor->hasPermission('threemagw', 'send');
                $permissions['receive'] = $visitor->hasPermission('threemagw', 'receive');
                $permissions['fetch']   = $visitor->hasPermission('threemagw', 'fetch');
                $permissions['lookup']  = $visitor->hasPermission('threemagw', 'lookup');
                $permissions['tfa']     = $visitor->hasPermission('threemagw', 'tfa');
                $permissions['credits'] = $visitor->hasAdminPermission('threemagw_showcredits');
            } else {
                if (!array_key_exists('permissions', $this->user)) {
                    if (!array_key_exists('global_permission_cache', $this->user) || !$this->user['global_permission_cache']) {
                        // used code by XenForo_Visitor::setup
                        // get permissions from cache
                        $perms = XenForo_Model::create('XenForo_Model_Permission')->rebuildPermissionCombinationById(
                            $this->user['permission_combination_id']
                        );
                        $this->user['permissions'] = $perms ? $perms : [];
                    } else {
                        $this->user['permissions'] = XenForo_Permission::unserializePermissions($this->user['global_permission_cache']);
                    }
                }

                //get permissions
                $permissions['use']     = XenForo_Permission::hasPermission($this->user['permissions'], 'threemagw', 'use');
                $permissions['send']    = XenForo_Permission::hasPermission($this->user['permissions'], 'threemagw', 'send');
                $permissions['receive'] = XenForo_Permission::hasPermission($this->user['permissions'], 'threemagw', 'receive');
                $permissions['fetch']   = XenForo_Permission::hasPermission($this->user['permissions'], 'threemagw', 'fetch');
                $permissions['lookup']  = XenForo_Permission::hasPermission($this->user['permissions'], 'threemagw', 'lookup');
                $permissions['tfa']     = XenForo_Permission::hasPermission($this->user['permissions'], 'threemagw', 'tfa');
                // Getting admin permission would be extensive and admins should
                // also only have special permissions if they are really logged in.
                // Therefore all admin permissions are set to false
                $permissions['credits'] = false;
            }
            $this->permissions = $permissions;
        }

        // Return permission
        if ($action) {
            if (!array_key_exists($action, $permissions)) {
                // invalid action
                return false;
            }
            return $permissions[$action];
        }

        return $permissions;
    }

    /**
     * Returns the Threema ID associated to a phone number.
     *
     * In case of an error this does not throw an exception, but just returns false.
     *
     * @param  string            $phone Phone number in international E.164
     *                                  format, e.g. 41791234567
     * @throws XenForo_Exception
     * @return string|false
     */
    public function lookupPhone($phone)
    {
        // check permission
        if (!$this->hasPermission('lookup')) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_permission_error'));
        }

        /** @var array $threemaId Return value */
        $threemaId = false;

        /** @var Threema\MsgApi\Commands\Results\LookupIdResult $result */
        $result = $this->connector->keyLookupByPhoneNumber($phone);
        if ($result->isSuccess()) {
            $threemaId = $result->getId();
        }

        return $threemaId;
    }

    /**
     * Returns the Threema ID associated to a mail address.
     *
     * In case of an error this does not throw an exception, but just returns false.
     *
     * @param  string            $mail E-mail
     * @throws XenForo_Exception
     * @return string|false
     */
    public function lookupMail($mail)
    {
        // check permission
        if (!$this->hasPermission('lookup')) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_permission_error'));
        }

        /** @var array Return value $threemaId */
        $threemaId = false;

        /** @var Threema\MsgApi\Commands\Results\LookupIdResult $result */
        $result = $this->connector->keyLookupByEmail($mail);
        if ($result->isSuccess()) {
            $threemaId = $result->getId();
        }

        return $threemaId;
    }

    /**
     * Returns the capabilities of a Threema ID.
     *
     * In case of an error this does not throw an exception, but just returns false.
     *
     * @param  string                                           $threemaId
     * @throws XenForo_Exception
     * @return Threema\MsgApi\Commands\Results\CapabilityResult
     */
    public function getCapabilities($threemaId)
    {
        // check permission
        if (!$this->hasPermission('lookup')) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_permission_error'));
        }

        /** @var array $return Return value */
        $return = false;

        /** @var Threema\MsgApi\Commands\Results\LookupIdResult $result */
        $result = $this->connector->keyCapability($threemaId);
        if ($result->isSuccess()) {
            $return = $result;
        }

        return $return;
    }

    /**
     * Checks whether a Threema ID is valid ande exists.
     *
     * @param  string $threemaid      The Threema ID to check.
     * @param  string $type           The type of the Threema ID (personal, gateway, any)
     * @param  array  $error
     * @param  bool   $checkExistence Whether not only formal aspects should
     *                                be checked, but also the existence of the ID.
     * @return bool
     */
    public function checkThreemaId(&$threemaid, $type, &$error, $checkExistence = true)
    {
        $threemaid = strtoupper($threemaid);

        // check whether an id is formally correct
        if (!preg_match('/' . ThreemaGateway_Constants::RegExThreemaId[$type] . '/', $threemaid)) {
            $error[] = new XenForo_Phrase('threemagw_invalid_threema_id');
            return false;
        }

        if (!$checkExistence) {
            return true;
        }

        // fetches public key of an id to check whether it exists
        try {
            /** @var string $publicKey */
            $publicKey = $this->fetchPublicKey($threemaid);
        } catch (Exception $e) {
            $error[] = new XenForo_Phrase('threemagw_threema_id_does_not_exist');
            return false;
        }

        return true;
    }

    /**
     * Send a message without end-to-end encryption.
     *
     * @param  string            $threemaId
     * @param  string            $message
     * @throws XenForo_Exception
     * @return int               The message ID
     */
    public function sendSimple($threemaId, $message)
    {
        // check permission
        if (!$this->hasPermission('send')) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_permission_error'));
        }

        $threemaId = strtoupper($threemaId);

        /** @var Threema\MsgApi\Receiver $receiver */
        $receiver = new Receiver($threemaId, Receiver::TYPE_ID);

        /** @var Threema\MsgApi\Commands\Results\SendSimpleResult $result */
        $result = $this->connector->sendSimple($receiver, $message);

        if ($result->isSuccess()) {
            return $result->getMessageId();
        } else {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_sending_failed') . ' ' . $result->getErrorMessage());
        }
    }

    /**
     * Send a message to a Threema ID.
     *
     * @param string $threemaId The id where the message should be send to
     * @param string $message   The message to send (max 3500 characters)
     *
     * @throws XenForo_Exception
     * @return int
     */
    public function sendE2EText($threemaId, $message)
    {
        // check permission
        if (!$this->hasPermission('send')) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_permission_error'));
        }

        $threemaId = strtoupper($threemaId);

        $e2eHelper = new E2EHelper(
            ThreemaGateway_Handler_Key::hexToBin($this->PrivateKey),
            $this->connector
        );
        try {
            /** @var Threema\MsgApi\Commands\Results\SendE2EResult $result */
            $result = $e2eHelper->sendTextMessage($threemaId, $message);
        } catch (Exception $e) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_sending_failed') . ' ' . $e->getMessage());
        }

        if ($result->isSuccess()) {
            return $result->getMessageId();
        } else {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_sending_failed') . ' ' . $result->getErrorMessage());
        }
    }

    /**
     * Fetches the public key of an ID from the Threema server.
     *
     * @param string $threemaId The id whose public key should be fetched
     *
     * @throws XenForo_Exception
     * @return string
     */
    public function fetchPublicKey($threemaId)
    {
        // check permission
        if (!$this->hasPermission('fetch')) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_permission_error'));
        }

        /** @var Threema\MsgApi\Commands\Results\FetchPublicKeyResult $result */
        $result = $this->connector->fetchPublicKey($threemaId);
        if ($result->isSuccess()) {
            return $result->getPublicKey();
        } else {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_fetching_publickey_failed') . ' ' . $result->getErrorMessage());
        }
    }

    /**
     * Returns the remaining credits of the Gateway account.
     *
     * @throws XenForo_Exception
     * @return string
     */
    public function getCredits()
    {
        // check permission
        if (!$this->hasPermission('credits')) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_permission_error'));
        }

        /** @var Threema\MsgApi\Commands\Results\CreditsResult $result */
        $result = $this->connector->credits();

        if ($result->isSuccess()) {
            return $result->getCredits();
        } else {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_getting_credits_failed') . ' ' . $result->getErrorMessage());
        }
    }

    /**
     * Checks whether a user is the default user/the current "visitor".
     *
     * @param  int|null $userId A user id or null
     * @return bool
     */
    protected function userIsDefault($userId)
    {
        /** @var XenForo_Visitor $visitor */
        $visitor = XenForo_Visitor::getInstance();
        /** @var int $visitorUserId Visitor user id */
        $visitorUserId = $visitor->getUserId();

        return $userId === $visitorUserId || $userId === null;
    }
}

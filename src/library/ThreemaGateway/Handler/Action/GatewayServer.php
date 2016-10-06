<?php
/**
 * Works with the Threema Gateway server. Currently only looks up/fetches data
 * from it.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Handler_Action_GatewayServer extends ThreemaGateway_Handler_Action_Abstract
{
    /**
     * Returns the Threema ID associated to a phone number.
     *
     * In case of an error this does not throw an exception, but just returns false.
     *
     * @param  string            $phone Phone number (the best way is in international E.164
     *                                  format without `+`, e.g. 41791234567)
     * @throws XenForo_Exception
     * @return string|false
     */
    public function lookupPhone($phone)
    {
        // check permission
        if (!$this->permissions->hasPermission('lookup')) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_permission_error'));
        }

        //adjust phone number
        $phone = preg_replace('/\s+/', '', $phone); //strip whitespace
        if (substr($phone, 0, 1) == '+') {
            //remove leading +
            $phone = substr($phone, 1);
        }

        /** @var array $threemaId Return value */
        $threemaId = false;

        /** @var Threema\MsgApi\Commands\Results\LookupIdResult $result */
        $result = $this->getConnector()->keyLookupByPhoneNumber($phone);
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
        if (! $this->permissions->hasPermission('lookup')) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_permission_error'));
        }

        /** @var array Return value $threemaId */
        $threemaId = false;

        /** @var Threema\MsgApi\Commands\Results\LookupIdResult $result */
        $result = $this->getConnector()->keyLookupByEmail($mail);
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
     * @return Threema\MsgApi\Commands\Results\CapabilityResult
     */
    public function getCapabilities($threemaId)
    {
        // check permission
        if (! $this->permissions->hasPermission('lookup')) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_permission_error'));
        }

        /** @var array $return Return value */
        $return = false;

        /** @var Threema\MsgApi\Commands\Results\LookupIdResult $result */
        $result = $this->getConnector()->keyCapability($threemaId);
        if ($result->isSuccess()) {
            $return = $result;
        }

        return $return;
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
        if (!$this->permissions->hasPermission('credits')) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_permission_error'));
        }

        /** @var Threema\MsgApi\Commands\Results\CreditsResult $result */
        $result = $this->getConnector()->credits();

        if ($result->isSuccess()) {
            return $result->getCredits();
        } else {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_getting_credits_failed') . ' ' . $result->getErrorMessage());
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
        if (!$this->permissions->hasPermission('fetch')) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_permission_error'));
        }

        /** @var Threema\MsgApi\Commands\Results\FetchPublicKeyResult $result */
        $result = $this->getConnector()->fetchPublicKey($threemaId);
        if ($result->isSuccess()) {
            return $result->getPublicKey();
        } else {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_fetching_publickey_failed') . ' ' . $result->getErrorMessage());
        }
    }
}

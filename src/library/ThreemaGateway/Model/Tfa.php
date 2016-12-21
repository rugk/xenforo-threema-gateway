<?php
/**
 * Extends XenForos Tfa Model.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

class ThreemaGateway_Model_Tfa extends XFCP_ThreemaGateway_Model_Tfa
{
    /**
     * Array of attempt limits per provider
     *
     * Each entry for a provider has this format: [time, max attempts]
     *
     * @var array PROVIDER_TFA_LIMITS
     */
    const PROVIDER_TFA_LIMITS = [
        'threemagw_reversed' => [
            [60 * 5, 150], // in 5 minutes: 150 tries (every 2 seconds)
            [60, 30], // in 1 minutes: 30 tries (every 2 seconds)
            [12, 6], // in 12 seconds: 6 requests
            [8, 4], // in 8 seconds: 4 requests
        ],
        'threemagw_fast' => [
            [60 * 5, 150], // in 5 minutes: 150 tries (every 2 seconds)
            [60, 30], // in 1 minutes: 30 tries (every 2 seconds)
            [12, 6], // in 12 seconds: 6 requests
            [8, 4], // in 8 seconds: 4 requests
        ]
    ];

    /**
     * @var string
     */
    protected $providerId;

    /**
     * Setter for provider ID.
     *
     * @param string $providerId
     */
    public function threemagwSetProviderId($providerId)
    {
        $this->providerId = $providerId;
    }

    /**
     * Setter for provider ID.
     *
     * @return array
     */
    public function getTfaAttemptLimits()
    {
        /** @var array $defaultLimit the default limit by XenForo's implementation */
        $defaultLimit = parent::getTfaAttemptLimits();

        // check whether provider we handle, has a special limit configuration
        if (!array_key_exists($this->providerId, self::PROVIDER_TFA_LIMITS)) {
            return $defaultLimit;
        }

        /** @var XenForo_Options $xenOptions */
        $xenOptions = XenForo_Application::getOptions();

        // check whether required options are enabled
        /** @var bool $autoTriggerEnabled */
        $autoTriggerEnabled = false;
        switch ($this->providerId) {
            case 'threemagw_reversed':
                $autoTriggerEnabled = $xenOptions->threema_gateway_tfa_reversed_auto_trigger;
                break;
            case 'threemagw_fast':
                $autoTriggerEnabled = $xenOptions->threema_gateway_tfa_fast_auto_trigger;
                break;
        }

        if (!$autoTriggerEnabled) {
            return $defaultLimit;
        }

        return self::PROVIDER_TFA_LIMITS[$this->providerId];
    }
}

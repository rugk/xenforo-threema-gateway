<?php
/**
 * Common constants of add-on.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

/**
 * A collection of constants and default variables of this add-on.
 */
class ThreemaGateway_Constants
{
    /**
     * @var string ID of 2FA methode in general
     */
    const TFA_ID_PREFIX = 'threemagw';

    /**
     * @var int Priority of 2FA methode in general
     */
    const TFA_BASE_PRIORITY = 40;

    /**
     * @var array Regular expressions for Threema IDs
     */
    const REGEX_THREEMA_ID = [
        'gateway'  => '^\*[A-Za-z0-9]{7}$', // https://regex101.com/r/fF9hQ0/4
        'personal' => '^([A-Za-z0-9]{8})$', // https://regex101.com/r/sX9pY0/3
        'any'      => '^((\*[A-Za-z0-9]{7})|([A-Za-z0-9]{8}))$' // https://regex101.com/r/bF6xV5/7
    ];

    /**
     * @var string file path of threema callback php file
     */
    const CALLBACK_FILE = 'threema_callback.php';

    /**
     * @var string Type of delivery receipt messages
     */
    const MESSAGE_DELIVERY_RECEIPT_TYPE = [
        'received' => 1,
        'read' => 2,
        'userack' => 3,
        'userdec' => 4,
    ];

    /**
     * @var array TFA_PROVIDER_ARRAY list of all 2FA providers
     */
    const TFA_PROVIDER_ARRAY = [
        'threemagw_conventional',
        'threemagw_reversed',
        'threemagw_fast'
    ];
}

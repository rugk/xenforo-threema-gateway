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
    const TfaIDprefix = 'threemagw';

    /**
     * @var int Priority of 2FA methode in general
     */
    const TfaBasePriority = 40;

    /**
     * @var string Regular expressions for Threema IDs
     */
    const RegExThreemaId = [
        'gateway' => '^\*[A-Za-z0-9]{7}$', // https://regex101.com/r/fF9hQ0/4
        'personal' => '^([A-Za-z0-9]{8})$', // https://regex101.com/r/sX9pY0/3
        'any' => '^((\*[A-Za-z0-9]{7})|([A-Za-z0-9]{8}))$' // https://regex101.com/r/bF6xV5/7
    ];

    /**
     * @var string file path of threema callback php file
     */
    const CallbackFile = 'threema_callback.php';
}

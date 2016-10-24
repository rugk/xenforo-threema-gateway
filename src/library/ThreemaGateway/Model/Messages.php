<?php
/**
 * Model for messages stored in database.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Model_Messages extends XenForo_Model
{
    /**
     * @var string database table (prefix) for messages
     */
    const DbTableMessages = 'xf_threemagw_messages';

    /**
     * @var string database table for files
     */
    const DbTableFiles = 'xf_threemagw_files';

    /**
     * @var string database table for acknowledged messages
     */
    const DbTableAckMsgs = 'xf_threemagw_ackmsgs';

    /**
     * Initialises the model..
     */
    public function __construct()
    {
        // Nothing to do...
    }

    /**
     * Returns a singke message.
     *
     * @todo IMPLEMENT!
     * @param  string      $messageId
     * @return null|string
     */
    public function getMessage($threemaId)
    {
        // TODO: implement
    }
}

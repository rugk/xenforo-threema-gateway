<?php
/**
 * Allows one to get received messages.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

class ThreemaGateway_Handler_Receiver extends ThreemaGateway_Handler_Action_Abstract
{
    /**
     * @var XenForo_Input raw parameters
     */
    protected $input;

    /**
     * @var array filtered parameters
     */
    protected $filtered;

    /**
     * Check whether a specific message has been received and returns it.
     *
     * @param string $senderId The ID where you expect a message from.
     * @param string $keyword (optional) A keyword you look for.
     *
     * @throws XenForo_Exception
     * @return ???
     */
    public function getMessage($senderId, $keyword = null)
    {
        // check permission
        if (!$this->permissions->hasPermission('receive')) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_permission_error'));
        }

        // TODO
    }

    /**
	 * Initializes handling for processing a request callback.
	 *
	 * @param Zend_Controller_Request_Http $request
	 */
	public function initCallbackHandling(Zend_Controller_Request_Http $request)
	{
		$this->request = $request;
		$this->input = new XenForo_Input($request);

		$this->filtered = $this->_input->filter(array(
			'from' => XenForo_Input::STRING,
			'to' => XenForo_Input::STRING,
			'messageId' => XenForo_Input::STRING,
			'date' => XenForo_Input::DATE_TIME,
			'nonce' => XenForo_Input::STRING,
			'box' => XenForo_Input::STRING,
			'mac' => XenForo_Input::UNUM
		));

        var_dump($this->filtered);
	}

    /**
     * Validates the callback request. In case of failure let Gateway server
     * retry.
     *
     * @param string $errorString Output error string
     *
     * @return boolean
     */
    public function validateRequest(&$errorString)
    {
        return true;
    }

    /**
     * Validates the callback request. In case of failure let Gateway server
     * should not retry here as it likely would not help anyway.
     *
     * @param string $errorString Output error string
     *
     * @return boolean
     */
    public function validatePreConditions(&$errorString)
    {
        // simple, formal validation
        if ($this->cryptTool->stringCompare($this->filtered['to'], $this->settings->getId())) {
            $errorString = 'Invalid request';
            return false;
        }

        // HMAC validation
        if ($this->e2eHelper->checkMac(
            $this->filtered['from'],
            $this->filtered['to'],
            $this->filtered['messageId'],
            $this->filtered['date'],
            $this->filtered['nonce'],
            $this->filtered['box'],
            $this->filtered['mac'],
            $this->settings->getSecret()
            )) {
            $errorString = 'Unverifified request';
            return false;
        }

        return true;
    }
}

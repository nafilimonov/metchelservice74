<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Cloud Controller Loadaccesstoken
 *
 * @package HostCMS
 * @subpackage Cloud
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Cloud_Controller_Loadaccesstoken extends Admin_Form_Action_Controller
{
	/**
	 * Execute operation $operation
	 * @param mixed $operation Operation name
	 * @return mixed
	 */
	public function execute($operation = NULL)
	{
		$sAccessToken = '';

		$bStatus = TRUE;

		$iCode = Core_Array::getGet('code');
		if (!is_null($iCode))
		{
			$this->_object->code = strval($iCode);
			$this->_object->save();
		}

		$oCloud_Controller = Cloud_Controller::factory($this->_object->id);
		if (!is_null($oCloud_Controller))
		{
			try
			{
				$sAccessToken = $oCloud_Controller->getAccessToken();
			}
			catch (Core_Exception $e)
			{
				$sAccessToken = Core_Message::get($e->getMessage(), 'error');
				$bStatus = FALSE;
			}
		}

		$aResponse = array(
			'token' => $sAccessToken,
			'status'=> $bStatus
		);

		Core::showJson($aResponse);
	}
}
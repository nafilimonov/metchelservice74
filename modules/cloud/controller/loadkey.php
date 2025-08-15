<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Cloud Controller Loadkey
 *
 * @package HostCMS
 * @subpackage Cloud
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Cloud_Controller_Loadkey extends Admin_Form_Action_Controller
{
	/**
	 * Execute operation $operation
	 * @param mixed $operation Operation name
	 * @return mixed
	 */
	public function execute($operation = NULL)
	{
		$sPath = '';

		$bStatus = TRUE;

		$oCloud_Controller = Cloud_Controller::factory($this->_object->id);
		if (!is_null($oCloud_Controller))
		{
			try
			{
				$sPath = $oCloud_Controller->getOauthCodeUrl();
			}
			catch (Core_Exception $e)
			{
				$sPath = $e->getMessage();
				$bStatus = FALSE;
			}
		}

		$aResponse = array(
			'url' => $sPath,
			'status'=> $bStatus
		);

		Core::showJson($aResponse);
	}
}
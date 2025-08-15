<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Email_Controller_Tracking
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2021 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_Email_Controller_Tracking extends Core_Command_Controller
{
	/**
	 * Default controller action
	 * @return Core_Response
	 * @hostcms-event Siteuser_Email_Controller_Tracking.onBeforeShowAction
	 * @hostcms-event Siteuser_Email_Controller_Tracking.onAfterShowAction
	 */
	public function showAction()
	{
		Core_Event::notify(get_class($this) . '.onBeforeShowAction', $this);

		$oCore_Response = new Core_Response();
		$oCore_Response->header('X-Powered-By', 'HostCMS');

		$sGuid = Core_Array::getRequest('guid');

		if (!is_null($sGuid))
		{
			$oCore_Response
				->status(200)
				->header('Content-Type', 'image/png');

			$oSiteuser_Email = Core_Entity::factory('Siteuser_Email')->getByGuid($sGuid);

			if (!is_null($oSiteuser_Email) && is_null($oSiteuser_Email->read))
			{
				$oSiteuser_Email->read = Core_Date::timestamp2sql(time());
				$oSiteuser_Email->save();
			}

			// Transparent png, 1x1px
			$oCore_Response->body(
				base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8//9nPQAJdAN4FT/pzgAAAABJRU5ErkJggg==')
			);
		}
		else
		{
			$oCore_Response->body('Error 404: Not found')->status(404);
		}

		Core_Event::notify(get_class($this) . '.onAfterShowAction', $this, array($oCore_Response));

		return $oCore_Response;
	}
}
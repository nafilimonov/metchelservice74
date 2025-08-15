<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Session_Destroy_Controller
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_Session_Destroy_Controller extends Admin_Form_Action_Controller
{
	/**
	 * Execute
	 */
	public function execute($operation = NULL)
	{
		$oSiteuser_Sessions = Core_Entity::factory('Siteuser_Session');
		$oSiteuser_Sessions->queryBuilder()
			->where('siteuser_sessions.id', '!=', session_id());

		$aSiteuser_Sessions = $oSiteuser_Sessions->findAll(FALSE);

		foreach ($aSiteuser_Sessions as $oSiteuser_Session)
		{
			$oSiteuser_Session->destroy();
		}

		return $this;
	}
}
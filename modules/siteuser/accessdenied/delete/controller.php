<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Accessdenied_Delete_Controller
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Siteuser_Accessdenied_Delete_Controller extends Admin_Form_Action_Controller
{
	/**
	 * Execute
	 */
	public function execute($operation = NULL)
	{
		$limit = 500;
		do {
			$oSiteuser_Accessdenieds = Core_Entity::factory('Siteuser_Accessdenied');
			$oSiteuser_Accessdenieds->queryBuilder()
				->clear()
				->limit($limit);

			$aSiteuser_Accessdenieds = $oSiteuser_Accessdenieds->findAll(FALSE);
			foreach ($aSiteuser_Accessdenieds as $oSiteuser_Accessdenied)
			{
				$oSiteuser_Accessdenied->delete();
			}
		} while (count($aSiteuser_Accessdenieds) == $limit);

		return $this;
	}
}
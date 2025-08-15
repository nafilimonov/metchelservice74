<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Note_Controller_Markdeleted.
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Siteuser_Note_Controller_Markdeleted extends Admin_Form_Action_Controller
{
	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return bool
	 */
	public function execute($operation = NULL)
	{
		$iSiteuserId = intval(Core_Array::getGet('siteuser_id', 0));
		$oSiteuser = Core_Entity::factory('Siteuser')->getById($iSiteuserId);

		if (!is_null($oSiteuser))
		{
			$this->_object->markDeleted();

			$this->addMessage("<script>$(function() {
				$.adminLoad({ path: hostcmsBackend + '/siteuser/timeline/index.php', additionalParams: 'siteuser_id=" . $oSiteuser->id . "', windowId: 'id_content-siteuser-timeline' });
			});</script>");
		}

		return FALSE;
	}
}
<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Lead_Note_Controller_Markdeleted.
 *
 * @package HostCMS
 * @subpackage Lead
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Lead_Note_Controller_Markdeleted extends Admin_Form_Action_Controller
{
	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return bool
	 */
	public function execute($operation = NULL)
	{
		$iLeadId = intval(Core_Array::getGet('lead_id', 0));
		$oLead = Core_Entity::factory('Lead')->getById($iLeadId);

		if (!is_null($oLead))
		{
			$this->_object->markDeleted();

			$this->addMessage("<script>$(function() {
				$.adminLoad({ path: hostcmsBackend + '/lead/timeline/index.php', additionalParams: 'lead_id=" . $oLead->id . "', windowId: 'id_content-lead-timeline' });
			});</script>");
		}

		return FALSE;
	}
}
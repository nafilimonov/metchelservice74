<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Controller_Markdeleted.
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Siteuser_Controller_Markdeleted extends Admin_Form_Action_Controller
{
	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return bool
	 */
	public function execute($operation = NULL)
	{
		$oUser = Core_Auth::getCurrentUser();

		$bAllowedDelete = $this->_object->checkPermission2Delete($oUser);

		if (!$bAllowedDelete)
		{
			Core_Message::show(Core::_('Siteuser.delete_denied'), "error");
			return TRUE;
		}
		else
		{
			$this->_object->markDeleted();

			$this->addMessage("<script>$(function() {
				var jA = $('li[data-type=timeline] a');
				if (jA.length)
				{
					$.adminLoad({ path: jA.data('path'), additionalParams: jA.data('additional'), windowId: jA.data('window-id') });
				}
			});</script>");
		}

		return FALSE;
	}
}
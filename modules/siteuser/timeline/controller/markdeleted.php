<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Timeline_Controller_Markdeleted.
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Siteuser_Timeline_Controller_Markdeleted extends Admin_Form_Action_Controller
{
	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return bool
	 */
	public function execute($operation = NULL)
	{
		$this->_object->markDeleted();

		switch (get_class($this->_object))
		{
			case 'Crm_Note_Model':
				$type = 'note';
			break;
			default:
				$type = NULL;
		}

		if (!is_null($type))
		{
			$this->addMessage("<script>$(function() {
				var jA = $('li[data-type=" . $type . "] a');
				if (jA.length)
				{
					$.adminLoad({ path: jA.data('path'), additionalParams: jA.data('additional'), windowId: jA.data('window-id') });
				}
			});</script>");
		}

		return FALSE;
	}
}
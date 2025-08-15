<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Lead_Timeline_Controller_Markdeleted.
 *
 * @package HostCMS
 * @subpackage Lead
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Lead_Timeline_Controller_Markdeleted extends Admin_Form_Action_Controller
{
	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return bool
	 */
	public function execute($operation = NULL)
	{
		$class = get_class($this->_object);

		if ($class == 'Lead_Step_Model')
		{
			$this->_object->delete();
		}
		else
		{
			$this->_object->markDeleted();
		}

		switch ($class)
		{
			case 'Event_Model':
				$type = 'event';
			break;
			case 'Crm_Note_Model':
				$type = 'note';
			break;
			case 'Dms_Document_Model':
				$type = 'dms_document';
			break;
			case 'Lead_Shop_Item_Model':
				$type = 'shop_item';
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
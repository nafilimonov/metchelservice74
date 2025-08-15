<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Media_item_Format_Controller_Delete
 *
 * @package HostCMS
 * @subpackage Media
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Media_item_Format_Controller_Delete extends Admin_Form_Action_Controller
{
	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 */
	public function execute($operation = NULL)
	{
		$aChecked = $this->_Admin_Form_Controller->getChecked();

		// Clear checked list
		$this->_Admin_Form_Controller->clearChecked();

		foreach ($aChecked as $datasetKey => $checkedItems)
		{
			foreach ($checkedItems as $key => $value)
			{
				$oMedia_Item_Format = Core_Entity::factory('Media_Item_Format')->getById($key, FALSE);

				$oMedia_Item_Format->delete();

				$this->_Admin_Form_Controller->addMessage(
					"<script>$('.media-formats tr#row_0_{$key}').remove();</script>"
				);
			}
		}

		return TRUE;
	}
}
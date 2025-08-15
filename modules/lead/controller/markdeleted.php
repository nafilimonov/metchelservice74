<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Lead_Controller_Markdeleted.
 *
 * @package HostCMS
 * @subpackage Lead
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Lead_Controller_Markdeleted extends Admin_Form_Action_Controller
{
	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return bool
	 */
	public function execute($operation = NULL)
	{
		$this->_object->markDeleted();

		$this->addMessage("<script>$(function() {
			var jA = $('li[data-type=timeline] a');
			if (jA.length)
			{
				$.adminLoad({ path: jA.data('path'), additionalParams: jA.data('additional'), windowId: jA.data('window-id') });
			}
		});</script>");

		return FALSE;
	}
}
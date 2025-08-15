<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Advertisement_Group Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage Advertisement
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Advertisement_Group_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Prepare backend item's edit form
	 *
	 * @return self
	 */
	protected function _prepareForm()
	{
		parent::_prepareForm();

		$this->title(
			$this->_object->id
				? Core::_('Advertisement_Group.edit_title', $this->_object->name, FALSE)
				: Core::_('Advertisement_Group.add_title')
		);

		return $this;
	}
}
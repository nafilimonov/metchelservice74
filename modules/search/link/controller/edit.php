<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Search_Link_Controller_Edit.
 *
 * @package HostCMS
 * @subpackage Search
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Search_Link_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Set object
	 * @param object $object object
	 * @return self
	 */
	public function setObject($object)
	{
		parent::setObject($object);

		$oMainTab = $this->getTab('main');

		$oMainTab
			->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'));

		$oMainTab
			->move($this->getField('url')->format(
				array(
					'maxlen' => array('value' => 2000),
					'minlen' => array('value' => 1)
				)
			)->divAttr(array('class' => 'form-group col-xs-12')), $oMainRow1)
			->move($this->getField('ext')->divAttr(array('class' => 'form-group col-xs-12')), $oMainRow2);

		$this->title($this->_object->id
			? Core::_('Search_Link.edit_form_title')
			: Core::_('Search_Link.add_form_title')
		);

		return $this;
	}
}
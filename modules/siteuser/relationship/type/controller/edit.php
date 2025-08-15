<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Relationship_Type Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_Relationship_Type_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * List of sites
	 * @var array
	 */
	protected $_aSites = array();

	/**
	 * Load object's fields when object has been set
	 * После установки объекта загружаются данные о его полях
	 * @param object $object
	 * @return self
	 */
	public function setObject($object)
	{
		if (!$object->id)
		{
			$object->site_id = Core_Array::getGet('site_id');
		}

		return parent::setObject($object);
	}

	/**
	 * Prepare backend item's edit form
	 *
	 * @return self
	 */
	protected function _prepareForm()
	{
		parent::_prepareForm();

		$oMainTab = $this->getTab('main');
		$oAdditionalTab = $this->getTab('additional');

		$this->title($this->_object->id
			? Core::_('Siteuser_Relationship_Type.form_edit_add_title_edit', $this->_object->name, FALSE)
			: Core::_('Siteuser_Relationship_Type.form_edit_add_title_add')
		);

		$aSites = Core_Entity::factory('Site')->findAll(FALSE);
		foreach ($aSites as $oSites)
		{
			$this->_aSites[$oSites->id] = $oSites->name;
		}

		$oAdditionalTab->delete($this->getField('site_id'));

		$oSiteField = Admin_Form_Entity::factory('Select');
		$oSiteField
			->name('site_id')
			->caption(Core::_('Site.name'))
			->options($this->_aSites)
			->value($this->_object->site_id);

		$oMainTab->addAfter($oSiteField, $this->getField('name'));

		return $this;
	}
}
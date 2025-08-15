<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Group Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_Group_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
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

		$oMainTab
			->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow4 = Admin_Form_Entity::factory('Div')->class('row'));

		$oMainTab
			->move($this->getField('name'), $oMainRow1)
			->move($this->getField('description')->divAttr(array('class' => 'form-group col-xs-12')), $oMainRow2);

		$oAdditionalTab->delete($this->getField('site_id'));

		$oUser_Controller_Edit = new User_Controller_Edit($this->_Admin_Form_Action);

		// Список сайтов
		$oSelect_Sites = Admin_Form_Entity::factory('Select');
		$oSelect_Sites
			->options($oUser_Controller_Edit->fillSites())
			->name('site_id')
			->value($this->_object->site_id)
			->caption(Core::_('Siteuser_Group.site_id'))
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-3'));

		$oMainRow3->add($oSelect_Sites);

		$oMainTab
			->move($this->getField('sorting')->divAttr(array('class' => 'form-group col-xs-12 col-sm-3')), $oMainRow3)
			->move($this->getField('default')->divAttr(array('class' => 'form-group col-xs-12 col-sm-3 margin-top-21')), $oMainRow3);

		$this->title($this->_object->id
			? Core::_('Siteuser_Group.edit_title', $this->_object->name, FALSE)
			: Core::_('Siteuser_Group.add_title')
		);

		return $this;
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @return self
	 * @hostcms-event Siteuser_Group_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 */
	protected function _applyObjectProperty()
	{
		parent::_applyObjectProperty();

		if (Core_Array::getPost('default', 0))
		{
			$this->_object->setDefault();
		}

		Core_Event::notify(get_class($this) . '.onAfterRedeclaredApplyObjectProperty', $this, array($this->_Admin_Form_Controller));

		return $this;
	}

	/**
	 * Fill group list of site's users
	 * @param int $iSiteId site ID
	 * @return array
	 */
	public function fillSiteuserGroups($iSiteId)
	{
		$aReturn = array();
		$aChildren = Core_Entity::factory('Siteuser_Group')->getBySiteId($iSiteId);

		foreach ($aChildren as $oSiteuser_Group)
		{
			$aReturn[$oSiteuser_Group->id] = $oSiteuser_Group->name;
		}

		return $aReturn;
	}
}
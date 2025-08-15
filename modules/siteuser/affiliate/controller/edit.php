<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Affiliate Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_Affiliate_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Set object
	 * @param object $object object
	 * @return self
	 */
	public function setObject($object)
	{
		if (!$object->id)
		{
			$object->siteuser_id = Core_Array::getGet('siteuser_id');
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

		$oMainTab
			->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'));

		$oAdditionalTab->delete($this->getField('referral_siteuser_id'));

		$oSiteuser = $this->_object->referral_siteuser_id
			? $this->_object->Referral
			: NULL;

		$options = !is_null($oSiteuser)
			? array($oSiteuser->id => $oSiteuser->login . ' [' . $oSiteuser->id . ']')
			: array(0);

		$oSiteuserSelect = Admin_Form_Entity::factory('Select')
			->caption(Core::_('Siteuser_Affiliate.referral_siteuser_id'))
			->options($options)
			->name('referral_siteuser_id')
			->class('siteuser-tag')
			->style('width: 100%')
			->divAttr(array('class' => 'form-group col-xs-12'));

		$oMainRow1
			->add(
				Admin_Form_Entity::factory('Div')
					->class('form-group col-xs-12 col-sm-6 col-md-5 col-lg-4 no-padding no-margin-bottom')
					->add($oSiteuserSelect)
			);

		// Show button
		Siteuser_Controller_Edit::addSiteuserSelect2($oSiteuserSelect, $oSiteuser, $this->_Admin_Form_Controller);

		$oMainTab
			->move($this->getField('date')->divAttr(array('class' => 'form-group col-xs-12 col-sm-4 col-md-3')), $oMainRow1)
			->move($this->getField('active')->divAttr(array('class' => 'form-group col-xs-12 col-sm-5 col-md-4 margin-top-21')), $oMainRow1);

		$this->title($this->_object->id
			? Core::_('Siteuser_Affiliate.form_edit_add_title_edit')
			: Core::_('Siteuser_Affiliate.form_edit_add_title_add')
		);

		return $this;
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @hostcms-event Siteuser_Affiliate_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 * @return self
	 */
	protected function _applyObjectProperty()
	{
		$oSiteuser_Affiliate = !$this->_object->id
			? Core_Entity::factory('Siteuser_Affiliate')->getByReferral_siteuser_id($this->_formValues['referral_siteuser_id'])
			: NULL;

		is_null($oSiteuser_Affiliate)
			&& $this->_formValues['referral_siteuser_id'] != $this->_formValues['siteuser_id']
			&& parent::_applyObjectProperty();

		Core_Event::notify(get_class($this) . '.onAfterRedeclaredApplyObjectProperty', $this, array($this->_Admin_Form_Controller));

		return $this;
	}
}
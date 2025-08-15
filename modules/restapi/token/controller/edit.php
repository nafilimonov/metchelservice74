<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Restapi Token Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage Restapi
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Restapi_Token_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Prepare backend item's edit form
	 *
	 * @return self
	 */
	protected function _prepareForm()
	{
		parent::_prepareForm();

		$this->title($this->_object->id
			? Core::_('Restapi_Token.edit_title', $this->_object->token, FALSE)
			: Core::_('Restapi_Token.add_title')
		);

		$oMainTab = $this->getTab('main');
		$oAdditionalTab = $this->getTab('additional');

		$windowId = $this->_Admin_Form_Controller->getWindowId();

		$oMainTab
			->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'));

		$oMainTab
			->move($this->getField('token')->divAttr(array('class' => 'form-group col-xs-12')), $oMainRow1);

		if (!$this->_object->id)
		{
			$this->getField('token')->value(Restapi_Controller::createToken());
		}

		$oAdditionalTab->delete($this->getField('user_id'));

		$oSite = Core_Entity::factory('Site', CURRENT_SITE);

		$aSelectResponsibleUsers = $oSite->Companies->getUsersOptions();

		$oSelectResponsibleUsers = Admin_Form_Entity::factory('Select')
			->id('user_id')
			->options($aSelectResponsibleUsers)
			->name('new_user_id')
			->value($this->_object->user_id)
			->caption(Core::_('Restapi_Token.user_id'))
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'));

		$oScriptResponsibleUsers = Admin_Form_Entity::factory('Script')
			->value('$("#' . $windowId . ' #user_id").select2({
					dropdownParent: $("#' . $windowId . '"),
					placeholder: "",
					allowClear: true,
					//multiple: true,
					templateResult: $.templateResultItemResponsibleEmployees,
					escapeMarkup: function(m) { return m; },
					templateSelection: $.templateSelectionItemResponsibleEmployees,
					language: "' . Core_I18n::instance()->getLng() . '",
					width: "100%"
				});'
			);

		$oMainRow2
			->add($oSelectResponsibleUsers)
			->add($oScriptResponsibleUsers);

		$oMainTab
			->move($this->getField('datetime')->divAttr(array('class' => 'form-group col-xs-12 col-sm-3')), $oMainRow2)
			->move($this->getField('expire')->divAttr(array('class' => 'form-group col-xs-12 col-sm-3')), $oMainRow2)
			->move($this->getField('https')->divAttr(array('class' => 'form-group col-xs-12 col-sm-3')), $oMainRow3)
			->move($this->getField('active')->divAttr(array('class' => 'form-group col-xs-12 col-sm-4')), $oMainRow3);

		return $this;
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @hostcms-event Restapi_Token_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 * @return self
	 */
	protected function _applyObjectProperty()
	{
		parent::_applyObjectProperty();

		$this->_object->user_id = intval(Core_Array::get($this->_formValues, 'new_user_id'));
		$this->_object->save();

		Core_Event::notify(get_class($this) . '.onAfterRedeclaredApplyObjectProperty', $this, array($this->_Admin_Form_Controller));

		return $this;
	}
}
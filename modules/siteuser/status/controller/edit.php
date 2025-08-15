<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Status_Controller_Edit
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_Status_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
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
			? Core::_('Siteuser_Status.edit_siteuser_statuses_title', $this->_object->name, FALSE)
			: Core::_('Siteuser_Status.add_siteuser_statuses_title')
		);

		$oMainTab = $this->getTab('main');

		$oMainTab
			->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'));

				$sColorValue = ($this->_object->id && $this->getField('color')->value)
			? $this->getField('color')->value
			: '#eee';

		$this->getField('color')
			->colorpicker(TRUE)
			->value($sColorValue);

		$oMainTab
			->move($this->getField('name'), $oMainRow1)
			->move($this->getField('color')->set('data-control', 'hue'), $oMainRow2)
			->move($this->getField('description'), $oMainRow3);

		return $this;
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @hostcms-event Siteuser_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 */
	protected function _applyObjectProperty()
	{
		/*
		$password = Core_Array::getPost('password_first');

		if ($password != '' || is_null($this->_object->id))
		{
			$this->_object->password = Core_Hash::instance()->hash($password);
		}*/

		parent::_applyObjectProperty();

	}

	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return mixed
	 */
	public function execute($operation = NULL)
	{
		/*
		if (!is_null($operation) && $operation != '')
		{
			$login = Core_Array::getRequest('login');
			$id = Core_Array::getRequest('id');
			$site_id = Core_Array::getRequest('site_id');

			$oSameSiteuser = Core_Entity::factory('Site', $site_id)->Siteusers->getByLogin($login);

			// Дублирующийся логин
			if (!is_null($oSameSiteuser) && $oSameSiteuser->id != $id)
			{
				$this->addMessage(
					Core_Message::get(Core::_('Siteuser.login_error'), 'error')
				);
				return TRUE;
			}

			$email = Core_Array::getRequest('email');
			$oSameSiteuser = Core_Entity::factory('Site', $site_id)->Siteusers->getByEmail($email);

			// Дублирующийся e-mail
			if (!is_null($oSameSiteuser) && $oSameSiteuser->id != $id)
			{
				$this->addMessage(
					Core_Message::get(Core::_('Siteuser.email_error'), 'error')
				);
				return TRUE;
			}
		}*/

		return parent::execute($operation);
	}
}
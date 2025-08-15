<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_List Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Siteuser_List_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Prepare backend item's edit form
	 *
	 * @return self
	 */
	protected function _prepareForm()
	{
		parent::_prepareForm();

		$oMainTab = $this->getTab('main')->clear();

		$oMainTab
			->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'));

		$oMainRow1->add(
			Admin_Form_Entity::factory('Code')
				->html('
					<div class="col-xs-12">
						<div class="alert alert-success fade in">
							' . Core::_('Siteuser.format') . '
						</div>
					</div>
				')
		);

		$oMainRow2->add(
			Admin_Form_Entity::factory('Textarea')
				->rows(5)
				->caption(Core::_('Siteuser.add_list'))
				->name('siteuser_list')
				->divAttr(array('class' => 'form-group col-xs-12'))
		);

		return $this;
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @hostcms-event Siteuser_List_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 */
	protected function _applyObjectProperty()
	{
		// Массив данных о пользователей, каждый с новой строки
		// Формат строки:
		// email;login;password;name;surname
		$aRows = explode("\n", Core_Array::getPost('siteuser_list'));

		$oSite = Core_Entity::factory('Site', CURRENT_SITE);

		$oSiteuser_Group = $oSite->Siteuser_Groups->getDefault();

		foreach ($aRows as $sRow)
		{
			$aRow = explode(';', $sRow);

			$sEmail = trim($aRow[0]);

			if (Core_Valid::email($sEmail))
			{
				$oTmpSiteuser = $oSite->Siteusers->getByEmail($sEmail, FALSE);

				if (is_null($oTmpSiteuser))
				{
					$aConfig = Siteuser_Controller::getConfig($oSite->id);

					$oSiteuser = Core_Entity::factory('Siteuser');
					$oSiteuser->email = $sEmail;
					$oSiteuser->login = Core_Array::get($aRow, 1, $sEmail);
					$oSiteuser->password = Core_Hash::instance()->hash(
						strlen($sPassword = Core_Array::get($aRow, 2))
							? $sPassword
							: Core_Password::get($aConfig['generatePasswordLength'])
					);
					$oSiteuser->name = Core_Array::get($aRow, 3, '');
					$oSiteuser->surname = Core_Array::get($aRow, 4, '');
					$oSiteuser->site_id = CURRENT_SITE;
					$oSiteuser->active = 1;
					$oSiteuser->save();

					!is_null($oSiteuser_Group) && $oSiteuser_Group->add($oSiteuser);
				}
				else
				{
					// Показ ошибок
					Core_Message::show(Core::_('Maillist_Fascicle_Siteuser.error', $sEmail), 'error');
				}
			}
		}

		Core_Event::notify(get_class($this) . '.onAfterRedeclaredApplyObjectProperty', $this, array($this->_Admin_Form_Controller));
	}
}
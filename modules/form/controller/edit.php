<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Form Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage Form
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Form_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
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
			->add($oMainRow4 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow5 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRowCaptcha = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oProtectBlock = Admin_Form_Entity::factory('Div')->class('well with-header'))
			->add($oMainRowLead = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRowNotification = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow6 = Admin_Form_Entity::factory('Div')->class('row'));

		$oProtectBlock
			->add(Admin_Form_Entity::factory('Div')
				->class('header bordered-palegreen')
				->value(Core::_('Form.protect_header'))
			)
			->add($oProtectRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oProtectRow2 = Admin_Form_Entity::factory('Div')->class('row'));

		$oAdditionalTab
			->add($oAdditionalTabRow1 = Admin_Form_Entity::factory('Div')->class('row'));

		$oMainTab->move($this->getField('guid')->divAttr(array('class' => 'form-group col-xs-12')),$oAdditionalTabRow1);

		$oMainTab
			->move($this->getField('name'), $oMainRow1)
			->move($this->getField('description')
				->rows(10)
				->wysiwyg(Core::moduleIsActive('wysiwyg')
			), $oMainRow2);

		$oAdditionalTab->delete($this->getField('site_id'));

		$oUser_Controller_Edit = new User_Controller_Edit($this->_Admin_Form_Action);

		// Список сайтов
		$oSelect_Sites = Admin_Form_Entity::factory('Select')
			->options($oUser_Controller_Edit->fillSites())
			->name('site_id')
			->value($this->_object->site_id)
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'))
			->caption(Core::_('Form.site_id'));

		$oMainRow3->add($oSelect_Sites);

		$oMainTab
			->move($this->getField('email')->format(array('lib' => array()))->divAttr(array('class' => 'form-group col-xs-12 col-sm-6')), $oMainRow3)
			->move($this->getField('button_name')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6')), $oMainRow4)
			->move($this->getField('button_value')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6')), $oMainRow4)
			->move($this->getField('email_subject')->divAttr(array('class' => 'form-group col-xs-12')), $oMainRow5)
			->move($this->getField('success_text')
				->rows(10)
				->wysiwyg(Core::moduleIsActive('wysiwyg')
			), $oMainRow6);

		$windowId = $this->_Admin_Form_Controller->getWindowId();

		// Notification subscribers
		if (Core::moduleIsActive('notification'))
		{
			$oSite = Core_Entity::factory('Site', CURRENT_SITE);
			$aSelectSubscribers = $oSite->Companies->getUsersOptions();

			$oModule = Core::$modulesList['form'];

			$aSubscribers = array();

			$oNotification_Subscribers = Core_Entity::factory('Notification_Subscriber');
			$oNotification_Subscribers->queryBuilder()
				->where('notification_subscribers.module_id', '=', $oModule->id)
				->where('notification_subscribers.type', '=', 0)
				->where('notification_subscribers.entity_id', '=', $this->_object->id);

			$aNotification_Subscribers = $oNotification_Subscribers->findAll(FALSE);

			foreach ($aNotification_Subscribers as $oNotification_Subscriber)
			{
				$aSubscribers[] = $oNotification_Subscriber->user_id;
			}

			$oNotificationSubscribersSelect = Admin_Form_Entity::factory('Select')
				->caption(Core::_('Form.notification_subscribers'))
				->options($aSelectSubscribers)
				->name('notification_subscribers[]')
				->class('form-notification-subscribers')
				->value($aSubscribers)
				->style('width: 100%')
				->multiple('multiple')
				->divAttr(array('class' => 'form-group col-xs-12'));

			$oMainRowNotification->add($oNotificationSubscribersSelect);

			$html = '
				<script>
					$(function(){
						$("#' . $windowId . ' .form-notification-subscribers").select2({
							dropdownParent: $("#' . $windowId . '"),
							language: "' . Core_I18n::instance()->getLng() . '",
							placeholder: "' . Core::_('Form.type_subscriber') . '",
							allowClear: true,
							templateResult: $.templateResultItemResponsibleEmployees,
							escapeMarkup: function(m) { return m; },
							templateSelection: $.templateSelectionItemResponsibleEmployees,
							width: "100%"
						});
					})</script>
				';

			$oMainRowNotification->add(Admin_Form_Entity::factory('Code')->html($html));
		}

		$oMainTab
			->move($this->getField('use_captcha')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 col-md-3 margin-top-21')), $oProtectRow1)
			->move($this->getField('use_antispam')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 col-md-3 margin-top-21')), $oProtectRow1)
			->move($this->getField('csrf')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 col-md-3 margin-top-21')), $oProtectRow1)
			->move($this->getField('csrf_lifetime')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 col-md-3')), $oProtectRow1)
			;

		// if (Core::moduleIsActive('cache'))
		// {
			$sTextWell = "<div class=\"col-xs-12\"><div class=\"alert alert-warning\">" . Core::_('Form.protect_text') . "</div></div>";
			$oProtectRow2->add(Admin_Form_Entity::factory('Code')->html($sTextWell));
		// }

		if (Core::moduleIsActive('lead'))
		{
			$hidden = !$this->_object->create_lead ? ' hidden' : '';

			$oMainTab->move($this->getField('create_lead')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 col-md-3 margin-top-21')), $oMainRowCaptcha);

			$oAdditionalTab->delete($this->getField('crm_source_id'));

			$aMasCrmSources = array(array('value' => Core::_('Admin.none'), 'color' => '#aebec4'));

			$aCrm_Sources = Core_Entity::factory('Crm_Source')->findAll();
			foreach ($aCrm_Sources as $oCrm_Source)
			{
				$aMasCrmSources[$oCrm_Source->id] = array(
					'value' => $oCrm_Source->name,
					'color' => $oCrm_Source->color,
					'icon' => $oCrm_Source->icon
				);
			}

			$oDropdownlistCrmSources = Admin_Form_Entity::factory('Dropdownlist')
				->options($aMasCrmSources)
				->name('crm_source_id')
				->value($this->_object->crm_source_id)
				->caption(Core::_('Form.crm_source_id'))
				->divAttr(array('class' => 'form-group col-xs-12 col-sm-3 crm-source' . $hidden));

			$oMainRowCaptcha
				->add($oDropdownlistCrmSources);

			$oFormTabConformity = Admin_Form_Entity::factory('Tab')
				->caption(Core::_('Form.tab_conformity'))
				->name('Conformity')
				->class('lead-tab' . $hidden);

			$oFormTabConformity
				->add($oFormTabConformityRow1 = Admin_Form_Entity::factory('Div')->class('row'));

			$this
				->addTabAfter($oFormTabConformity, $oMainTab);

			$script = '
				<script>
					$(function(){
						$("#' . $windowId . ' input[name = create_lead]").on("change", function(){
							$("#' . $windowId . ' .lead-tab, #' . $windowId . ' .crm-source").toggleClass("hidden");
						});
					})</script>
				';

			$oMainRowLead->add(Admin_Form_Entity::factory('Code')->html($script));

			$aForm_Lead_Conformities = $this->_object->Form_Lead_Conformities->findAll();

			$aMasFormField = array('...');

			$aAvailableFieldTypes = array(0, 2, 3, 5, 7, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19);

			$oForm_Fields = $this->_object->Form_Fields;
			$oForm_Fields->queryBuilder()
				->where('form_fields.type', 'IN', $aAvailableFieldTypes);

			$aForm_Fields = $oForm_Fields->findAll();

			foreach ($aForm_Fields as $oForm_Field)
			{
				$aMasFormField[$oForm_Field->id] = $oForm_Field->caption;
			}

			$aConformities = array(
				'' => array(
					'value' => '...',
					'attr' => array('style' => 'background-color: #F5F5F5')
				),
				'name' => array(
					'value' => Core::_('Lead.name'),
					'attr' => array('style' => 'background-color: #EEEDE7')
				),
				'surname' => array(
					'value' => Core::_('Lead.surname'),
					'attr' => array('style' => 'background-color: #EEEDE7')
				),
				'patronymic' => array(
					'value' => Core::_('Lead.patronymic'),
					'attr' => array('style' => 'background-color: #EEEDE7')
				),
				'post' => array(
					'value' => Core::_('Lead.post'),
					'attr' => array('style' => 'background-color: #EEEDE7')
				),
				'company' => array(
					'value' => Core::_('Lead.company'),
					'attr' => array('style' => 'background-color: #EEEDE7')
				),
				'comment' => array(
					'value' => Core::_('Lead.comment'),
					'attr' => array('style' => 'background-color: #EEEDE7')
				),
				'amount' => array(
					'value' => Core::_('Lead.amount'),
					'attr' => array('style' => 'background-color: #EEEDE7')
				),
				'email' => array(
					'value' => Core::_('Directory_Email.email'),
					'attr' => array('style' => 'background-color: #EEEDE7')
				),
				'phone' => array(
					'value' => Core::_('Directory_Phone.phone'),
					'attr' => array('style' => 'background-color: #EEEDE7')
				),
				'postcode' => array(
					'value' => Core::_('Directory_Address.address_postcode'),
					'attr' => array('style' => 'background-color: #EEEDE7')
				),
				'country' => array(
					'value' => Core::_('Directory_Address.address_country'),
					'attr' => array('style' => 'background-color: #EEEDE7')
				),
				'city' => array(
					'value' => Core::_('Directory_Address.address_city'),
					'attr' => array('style' => 'background-color: #EEEDE7')
				),
				'address' => array(
					'value' => Core::_('Directory_Address.address'),
					'attr' => array('style' => 'background-color: #EEEDE7')
				),
				'website' => array(
					'value' => Core::_('Directory_Website.site'),
					'attr' => array('style' => 'background-color: #EEEDE7')
				),
				'note' => array(
					'value' => Core::_('Lead.note'),
					'attr' => array('style' => 'background-color: #ECD446')
				)
			);

			if (Core::moduleIsActive('field'))
			{
				$aForm_Fields = Field_Controller::getFields('lead');
				foreach ($aForm_Fields as $oForm_Field)
				{
					$aConformities['field_' . $oForm_Field->id] = array(
						'value' => $oForm_Field->name,
						'attr' => array('style' => 'background-color: #BBCEF2')
					);
				}
			}

			if (count($aForm_Lead_Conformities))
			{
				foreach ($aForm_Lead_Conformities as $oForm_Lead_Conformity)
				{
					$oFormTabConformity->add($oFormTabConformityRow = Admin_Form_Entity::factory('Div')->class('row'));

					 $oFormTabConformityRow->add(
							Admin_Form_Entity::factory('Select')
								->options($aMasFormField)
								->name('form_lead_comformity_field#' . $oForm_Lead_Conformity->id)
								->value($oForm_Lead_Conformity->form_field_id)
								->caption(Core::_('Form.form_fields'))
								->divAttr(array('class' => 'form-group col-xs-4'))
						)
						->add(
							Admin_Form_Entity::factory('Select')
								->options($aConformities)
								->name('form_lead_comformity#' . $oForm_Lead_Conformity->id)
								->value($oForm_Lead_Conformity->conformity)
								->caption(Core::_('Form.lead_conformity'))
								->divAttr(array('class' => 'form-group col-xs-4'))
						)
						->add(
							Admin_Form_Entity::factory('Div') // div с кноками + и -
								->class('no-padding add-remove-property margin-top-23 pull-left')
								->add(
									Admin_Form_Entity::factory('Code')
										->html('<div class="btn btn-palegreen" onclick="$.cloneFormRow(this); event.stopPropagation();"><i class="fa fa-plus-circle close"></i></div><div class="btn btn-darkorange btn-delete" onclick="$.deleteFormRow(this); event.stopPropagation();"><i class="fa fa-minus-circle close"></i></div>')
								)
						);
				}
			}
			else
			{
				 $oFormTabConformityRow1->add(
						Admin_Form_Entity::factory('Select')
							->options($aMasFormField)
							->name('form_lead_comformity_field[]')
							->value(0)
							->caption(Core::_('Form.form_fields'))
							->divAttr(array('class' => 'form-group col-xs-4'))
					)
					->add(
						Admin_Form_Entity::factory('Select')
							->options($aConformities)
							->name('form_lead_comformity[]')
							->value('')
							->caption(Core::_('Form.lead_conformity'))
							->divAttr(array('class' => 'form-group col-xs-4'))
					)
					->add(
						Admin_Form_Entity::factory('Div') // div с кноками + и -
							->class('no-padding add-remove-property margin-top-23 pull-left')
							->add(
								Admin_Form_Entity::factory('Code')
									->html('<div class="btn btn-palegreen" onclick="$.cloneFormRow(this); event.stopPropagation();"><i class="fa fa-plus-circle close"></i></div><div class="btn btn-darkorange btn-delete hide" onclick="$.deleteFormRow(this); event.stopPropagation();"><i class="fa fa-minus-circle close"></i></div>')
							)
					);
			}
		}

		$this->title($this->_object->id
			? Core::_('Form.edit_title', $this->_object->name, FALSE)
			: Core::_('Form.add_title')
		);

		return $this;
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @hostcms-event Form_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 */
	protected function _applyObjectProperty()
	{
		// Backup revision
		if (Core::moduleIsActive('revision') && $this->_object->id)
		{
			$this->_object->backupRevision();
		}

		parent::_applyObjectProperty();

		$this->_object->createDir();

		$windowId = $this->_Admin_Form_Controller->getWindowId();

		// Соответствия, установленные значения
		$aForm_Lead_Conformities = $this->_object->Form_Lead_Conformities->findAll();
		foreach ($aForm_Lead_Conformities as $oForm_Lead_Conformity)
		{
			$iFormLeadComformityField = intval(Core_Array::getPost("form_lead_comformity_field#{$oForm_Lead_Conformity->id}"));

			if ($iFormLeadComformityField)
			{
				$oForm_Lead_Conformity
					->form_field_id($iFormLeadComformityField)
					->conformity(strval(Core_Array::getPost("form_lead_comformity#{$oForm_Lead_Conformity->id}")))
					->save();
			}
			else
			{
				// Удаляем пустую строку с полями
				ob_start();
				Core_Html_Entity::factory('Script')
					->value("$.deleteFormRow($(\"#{$windowId} select[name='form_lead_comformity_field#{$oForm_Lead_Conformity->id}']\").closest('.row').find('.btn-delete').get(0));")
					->execute();

				$this->_Admin_Form_Controller->addMessage(ob_get_clean());
				$oForm_Lead_Conformity->delete();
			}
		}

		// Соответствия, новые значения
		$aFormLeadComformityFields = Core_Array::getPost('form_lead_comformity_field', array());
		$aLeadConformities = Core_Array::getPost('form_lead_comformity', array());

		$i = 0;

		foreach ($aFormLeadComformityFields as $key => $form_field_id)
		{
			$form_field_id = intval($form_field_id);

			if ($form_field_id)
			{
				$oForm_Lead_Conformity = Core_Entity::factory('Form_Lead_Conformity');
				$oForm_Lead_Conformity->form_id = $this->_object->id;
				$oForm_Lead_Conformity->form_field_id = $form_field_id;
				$oForm_Lead_Conformity->conformity = strval(Core_Array::get($aLeadConformities, $key));
				$oForm_Lead_Conformity->save();

				ob_start();
				Core_Html_Entity::factory('Script')
					->value("$(\"#{$windowId} select[name='form_lead_comformity_field\\[\\]']\").eq({$i}).prop('name', 'form_lead_comformity_field#{$oForm_Lead_Conformity->id}').closest('.row').find('.btn-delete').removeClass('hide');
					$(\"#{$windowId} select[name='form_lead_comformity\\[\\]']\").eq({$i}).prop('name', 'form_lead_comformity#{$oForm_Lead_Conformity->id}');
					")
					->execute();

				$this->_Admin_Form_Controller->addMessage(ob_get_clean());
			}
			else
			{
				$i++;
			}
		}

		if (Core::moduleIsActive('notification'))
		{
			$oModule = Core::$modulesList['form'];

			$aRecievedNotificationSubscribers = Core_Array::getPost('notification_subscribers', array());
			!is_array($aRecievedNotificationSubscribers) && $aRecievedNotificationSubscribers = array();

			$aTmp = array();

			// Выбранные сотрудники
			$oNotification_Subscribers = Core_Entity::factory('Notification_Subscriber');
			$oNotification_Subscribers->queryBuilder()
				->where('notification_subscribers.module_id', '=', $oModule->id)
				->where('notification_subscribers.type', '=', 0)
				->where('notification_subscribers.entity_id', '=', $this->_object->id)
				;

			$aNotification_Subscribers = $oNotification_Subscribers->findAll(FALSE);

			foreach ($aNotification_Subscribers as $oNotification_Subscriber)
			{
				!in_array($oNotification_Subscriber->user_id, $aRecievedNotificationSubscribers)
					? $oNotification_Subscriber->delete()
					: $aTmp[] = $oNotification_Subscriber->user_id;
			}

			// $aNewRecievedNotificationSubscribers = array_diff($aRecievedNotificationSubscribers, $aTmp);

			foreach ($aRecievedNotificationSubscribers as $user_id)
			{
				$oNotification_Subscribers = Core_Entity::factory('Notification_Subscriber');
				$oNotification_Subscribers->queryBuilder()
					->where('notification_subscribers.module_id', '=', $oModule->id)
					->where('notification_subscribers.user_id', '=', intval($user_id))
					->where('notification_subscribers.entity_id', '=', $this->_object->id)
					;

				$iCount = $oNotification_Subscribers->getCount();

				if (!$iCount)
				{
					$oNotification_Subscriber = Core_Entity::factory('Notification_Subscriber');
					$oNotification_Subscriber
						->module_id($oModule->id)
						->type(0)
						->entity_id($this->_object->id)
						->user_id($user_id)
						->save();
				}
			}
		}

		Core::moduleIsActive('wysiwyg') && Wysiwyg_Controller::uploadImages($this->_formValues, $this->_object, $this->_Admin_Form_Controller);

		Core_Event::notify(get_class($this) . '.onAfterRedeclaredApplyObjectProperty', $this, array($this->_Admin_Form_Controller));
	}
}
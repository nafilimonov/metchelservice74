<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Person_Controller_Edit
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Siteuser_Person_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Set object
	 * @param object $object object
	 * @return self
	 */
	public function setObject($object)
	{
		$iSiteuserId = Core_Array::getGet('siteuser_id');

		if (!$object->id)
		{
			$object->siteuser_id = $iSiteuserId;
		}

		$this
			->addSkipColumn('image')
			->addSkipColumn('~postcode')
			->addSkipColumn('~city')
			->addSkipColumn('~country')
			->addSkipColumn('~address');

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

		$object = $this->_object;

		$modelName = $object->getModelName();

		$windowId = $this->_Admin_Form_Controller->getWindowId();

		$oMainTab = $this->getTab('main');

		$oSiteuser = $this->_object->siteuser_id
			? Core_Entity::factory('Siteuser', $this->_object->siteuser_id)
			: NULL;

		switch ($modelName)
		{
			case 'siteuser_person':

				$title = $object->id
					? Core::_('Siteuser_Person.siteuser_person_edit_form_title', $object->getFullName(), $oSiteuser ? $oSiteuser->login : '', FALSE)
					: Core::_('Siteuser_Person.siteuser_person_add_form_title', $oSiteuser ? $oSiteuser->login : '', FALSE);

				// Адреса
				$oSiteuserCompanyAddressesRow = Directory_Controller_Tab::instance('address')
					->title(Core::_('Directory_Address.address'))
					->relation($object->Siteuser_Person_Directory_Addresses)
					->showPublicityControlElement(TRUE)
					->execute();

				// Email'ы
				$oSiteuserPersonEmailsRow = Directory_Controller_Tab::instance('email')
					->title(Core::_('Directory_Email.emails'))
					->relation($object->Siteuser_Person_Directory_Emails)
					->showPublicityControlElement(TRUE)
					->execute();

				// Телефоны
				$oSiteuserPersonPhonesRow = Directory_Controller_Tab::instance('phone')
					->title(Core::_('Directory_Phone.phones'))
					->relation($object->Siteuser_Person_Directory_Phones)
					->showPublicityControlElement(TRUE)
					->execute();

				// Социальные сети
				$oSiteuserPersonSocialsRow = Directory_Controller_Tab::instance('social')
					->title(Core::_('Directory_Social.socials'))
					->relation($object->Siteuser_Person_Directory_Socials)
					->showPublicityControlElement(TRUE)
					->execute();

				// Мессенджеры
				$oSiteuserPersonMessengersRow = Directory_Controller_Tab::instance('messenger')
					->title(Core::_('Directory_Messenger.messengers'))
					->relation($object->Siteuser_Person_Directory_Messengers)
					->showPublicityControlElement(TRUE)
					->execute();

				// Сайты
				$oSiteuserPersonWebsitesRow = Directory_Controller_Tab::instance('website')
					->title(Core::_('Directory_Website.sites'))
					->relation($object->Siteuser_Person_Directory_Websites)
					->showPublicityControlElement(TRUE)
					->execute();

				$oMainTab
					->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oSiteuserPersonPhonesRow)
					->add($oSiteuserPersonEmailsRow)
					->add($oSiteuserCompanyAddressesRow)
					// ->add($oMainRow4 = Admin_Form_Entity::factory('Div')->class('row'))
					// ->add($oMainRow5 = Admin_Form_Entity::factory('Div')->class('row'))
					// ->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oSiteuserPersonSocialsRow)
					->add($oSiteuserPersonMessengersRow)
					->add($oSiteuserPersonWebsitesRow);

				$oMainTab
					->move($this->getField('surname')->divAttr(array('class' => 'form-group col-xs-12 col-md-4'))->class('form-control input-lg semi-bold black'), $oMainRow1)
					->move($this->getField('name')->divAttr(array('class' => 'form-group col-xs-12 col-md-4'))->class('form-control input-lg semi-bold black'), $oMainRow1)
					->move($this->getField('patronymic')->divAttr(array('class' => 'form-group col-xs-12 col-md-4'))->class('form-control input-lg'), $oMainRow1)
					->move($this->getField('birthday')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 col-lg-2')), $oMainRow3)
					->move($this->getField('post')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 col-lg-3')), $oMainRow3);

				$sFormPath = $this->_Admin_Form_Controller->getPath();

				$aConfig = Core_Config::instance()->get('siteuser_person_config', array()) + array (
					'max_height' => 130,
					'max_width' => 130
				);

				// Изображение
				$oImageField = Admin_Form_Entity::factory('File');
				$oImageField
					->type('file')
					->caption(Core::_('Siteuser_Person.image'))
					->name('image')
					->id('image')
					->largeImage(
						array(
							'max_width' => $aConfig['max_width'],
							'max_height' => $aConfig['max_height'],
							'path' => $object->getImageFilePath() != '' && Core_File::isFile($object->getImageFilePath())
								? $object->getImageFileHref()
								: '',
							'show_params' => TRUE,
							'preserve_aspect_ratio_checkbox_checked' => FALSE,
							// deleteWatermarkFile
							'delete_onclick' => "$.adminLoad({path: '{$sFormPath}', additionalParams: 'hostcms[checked][0][{$object->id}]=1&show=person', action: 'deleteImageFile', windowId: '{$windowId}'}); return false",
							'place_watermark_checkbox' => FALSE,
							'place_watermark_x_show' => FALSE,
							'place_watermark_y_show' => FALSE
						)
					)
					->smallImage(
						array(
							'show' => FALSE
						)
					)
					->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 col-lg-3'));

				$oMainRow3->add($oImageField);

				$oMainTab->delete($this->getField('sex'));

				// Добавляем пол физ. лица
				$oSiteuserPersonSex = Admin_Form_Entity::factory('Radiogroup')
					->name('sex')
					->id('sex' . time())
					->caption(Core::_('Siteuser_Person.sex'))
					->value($object->sex)
					->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 col-lg-4 rounded-radio-group'))
					->radio(array(
						0 => Core::_('Siteuser_Person.male'),
						1 => Core::_('Siteuser_Person.female')
					))
					->ico(
						array(
							0 => 'fa-mars',
							1 => 'fa-venus'
					))
					->colors(
						array(
							0 => 'btn-sky',
							1 => 'btn-pink'
						)
					);

				$oMainRow3->add($oSiteuserPersonSex);

				/*$this->getField('postcode')->divAttr(array('class' => 'form-group col-xs-12 col-sm-4'));
				$this->getField('country')->divAttr(array('class' => 'form-group col-xs-12 col-sm-4'));
				$this->getField('city')->divAttr(array('class' => 'form-group col-xs-12 col-sm-4'));

				$oMainTab
					->move($this->getField('postcode'), $oMainRow4)
					->move($this->getField('country'), $oMainRow4)
					->move($this->getField('city'), $oMainRow4);

				$this->getField('address')->divAttr(array('class' => 'form-group col-xs-12'));
				$oMainTab
					->move($this->getField('address'), $oMainRow5);*/
			break;
			case 'siteuser_company':
			default:
				$title = $object->id
					? Core::_('Siteuser_Company.siteuser_company_edit_form_title', $object->name, $oSiteuser ? $oSiteuser->login : '', FALSE)
					: Core::_('Siteuser_Company.siteuser_company_add_form_title', $oSiteuser ? $oSiteuser->login : '', FALSE);

				$oMainTab = $this->getTab('main');

				$oTabContacts = Admin_Form_Entity::factory('Tab')
					->caption(Core::_('Siteuser_Company.tabContacts'))
					->name('Contacts');

				$oTabBankingDetails = Admin_Form_Entity::factory('Tab')
					->caption(Core::_('Siteuser_Company.tabBankingDetails'))
					->name('BankingDetails');

				$this
					->addTabAfter($oTabContacts, $oMainTab)
					->addTabAfter($oTabBankingDetails, $oTabContacts);

				$oMainTab
					->add($oMainTabRow1 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oMainTabRow2 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oMainTabRow3 = Admin_Form_Entity::factory('Div')->class('row'));

				$oTabBankingDetails
					->add($oTabBankingDetailsRow1 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oTabBankingDetailsRow2 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oTabBankingDetailsRow3 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oTabBankingDetailsRow4 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oTabBankingDetailsRow5 = Admin_Form_Entity::factory('Div')->class('row'));

				$oMainTab
					->move($this->getField('name')->class('form-control input-lg semi-bold black'), $oMainTabRow1)
					->move($this->getField('description'), $oMainTabRow2)
					->move($this->getField('business_area')->divAttr(array('class' => 'form-group col-xs-12 col-md-4')), $oMainTabRow2)
					->move($this->getField('headcount')->divAttr(array('class' => 'form-group col-xs-12 col-md-4')), $oMainTabRow2)
					->move($this->getField('annual_turnover')->divAttr(array('class' => 'form-group col-xs-12 col-md-4')), $oMainTabRow2)
					->move($this->getField('tin')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6')), $oTabBankingDetailsRow1)
					->move($this->getField('kpp')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6')), $oTabBankingDetailsRow1)
					->move($this->getField('current_account')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6')), $oTabBankingDetailsRow2)
					->move($this->getField('correspondent_account')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6')), $oTabBankingDetailsRow2)
					->move($this->getField('bank_name')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6')), $oTabBankingDetailsRow3)
					->move($this->getField('bank_address')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6')), $oTabBankingDetailsRow3)
					->move($this->getField('bic')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6')), $oTabBankingDetailsRow4)
					->move($this->getField('bank_account')->divAttr(array('class' => 'form-group col-xs-12')), $oTabBankingDetailsRow5);

				// Адреса
				$oSiteuserCompanyAddressesRow = Directory_Controller_Tab::instance('address')
					->title(Core::_('Directory_Address.address'))
					->relation($object->Siteuser_Company_Directory_Addresses)
					->showPublicityControlElement(TRUE)
					->execute();

				// Телефоны
				$oSiteuserCompanyPhonesRow = Directory_Controller_Tab::instance('phone')
					->title(Core::_('Directory_Phone.phones'))
					->relation($object->Siteuser_Company_Directory_Phones)
					->showPublicityControlElement(TRUE)
					->execute();

				// Email'ы
				$oSiteuserCompanyEmailsRow = Directory_Controller_Tab::instance('email')
					->title(Core::_('Directory_Email.emails'))
					->relation($object->Siteuser_Company_Directory_Emails)
					->showPublicityControlElement(TRUE)
					->execute();

				// Социальные сети
				$oSiteuserCompanySocialsRow = Directory_Controller_Tab::instance('social')
					->title(Core::_('Directory_Social.socials'))
					->relation($object->Siteuser_Company_Directory_Socials)
					->showPublicityControlElement(TRUE)
					->execute();

				// Мессенджеры
				$oSiteuserCompanyMessengersRow = Directory_Controller_Tab::instance('messenger')
					->title(Core::_('Directory_Messenger.messengers'))
					->relation($object->Siteuser_Company_Directory_Messengers)
					->showPublicityControlElement(TRUE)
					->execute();

				// Сайты
				$oSiteuserCompanyWebsitesRow = Directory_Controller_Tab::instance('website')
					->title(Core::_('Directory_Website.sites'))
					->relation($object->Siteuser_Company_Directory_Websites)
					->showPublicityControlElement(TRUE)
					->execute();

				$oTabContacts
					->add($oSiteuserCompanyPhonesRow)
					->add($oSiteuserCompanyEmailsRow)
					->add($oSiteuserCompanyAddressesRow)
					->add($oSiteuserCompanySocialsRow)
					->add($oSiteuserCompanyMessengersRow)
					->add($oSiteuserCompanyWebsitesRow);

				$sFormPath = $this->_Admin_Form_Controller->getPath();

				$aConfig = Core_Config::instance()->get('siteuser_company_config', array()) + array (
					'max_height' => 130,
					'max_width' => 130
				);

				// Изображение
				$oImageField = Admin_Form_Entity::factory('File');
				$oImageField
					->type('file')
					->caption(Core::_('Siteuser_Company.image'))
					->name('image')
					->id('image')
					->largeImage(
						array(
							'max_width' => $aConfig['max_width'],
							'max_height' => $aConfig['max_height'],
							'path' => $object->getImageFilePath() != '' && Core_File::isFile($object->getImageFilePath())
								? $object->getImageFileHref()
								: '',
							'show_params' => TRUE,
							'preserve_aspect_ratio_checkbox_checked' => FALSE,
							// deleteWatermarkFile
							'delete_onclick' => "$.adminLoad({path: '{$sFormPath}', additionalParams: 'hostcms[checked][0][{$object->id}]=1&show=company', action: 'deleteImageFile', windowId: '{$windowId}'}); return false",
							'place_watermark_checkbox' => FALSE,
							'place_watermark_x_show' => FALSE,
							'place_watermark_y_show' => FALSE
						)
					)
					->smallImage(
						array(
							'show' => FALSE
						)
					)
					// ->divAttr(array('class' => 'input-group col-lg-6 col-md-6 col-sm-12 col-xs-12'))
					;

				$oMainTabRow3->add($oImageField);

			break;
		}

		// cut (...)
		!$oSiteuser && $title = preg_replace('/\(.*?\)/', '', $title);

		$this->title($title);

		return $this;
	}

	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return mixed
	 */
	public function execute($operation = NULL)
	{
		// Всегда id_content
		$sJsRefresh = '<script>
		if ($("#id_content .admin_table_filter").length && typeof _windowSettings != \'undefined\') {
			$(\'#id_content #refresh-toggler\').click();
		}</script>';

		switch ($operation)
		{
			case 'saveModal':
			case 'applyModal':
				$operation == 'saveModal' && $this->addMessage($sJsRefresh);
				$operation == 'applyModal' && $this->addContent($sJsRefresh);
			break;
			case 'markDeleted':
				$this->_object->markDeleted();
				$this->addMessage($sJsRefresh);
			break;
		}

		return parent::execute($operation);
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @hostcms-event Informationsystem_Item_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 */
	protected function _applyObjectProperty()
	{
		$this->addSkipColumn('sex');

		parent::_applyObjectProperty();

		$object = $this->_object;

		if (isset($this->_formValues['sex']))
		{
			$object->sex = intval($this->_formValues['sex']);
			$object->save();
		}

		$modelName = $object->getModelName();

		Directory_Controller_Tab::instance('address')->applyObjectProperty($this->_Admin_Form_Controller, $this->_object);
		Directory_Controller_Tab::instance('email')->applyObjectProperty($this->_Admin_Form_Controller, $this->_object);
		Directory_Controller_Tab::instance('phone')->applyObjectProperty($this->_Admin_Form_Controller, $this->_object);
		Directory_Controller_Tab::instance('social')->applyObjectProperty($this->_Admin_Form_Controller, $this->_object);
		Directory_Controller_Tab::instance('website')->applyObjectProperty($this->_Admin_Form_Controller, $this->_object);
		Directory_Controller_Tab::instance('messenger')->applyObjectProperty($this->_Admin_Form_Controller, $this->_object);

		if (
			// Поле файла существует
			!is_null($aFileData = Core_Array::getFiles('image', NULL))
			// и передан файл
			&& intval($aFileData['size']) > 0)
		{
			if (Core_File::isValidExtension($aFileData['name'], array('JPG', 'JPEG', 'GIF', 'PNG')))
			{
				$fileExtension = Core_File::getExtension($aFileData['name']);
				$sImageName = ($modelName == 'siteuser_person' ? 'image.' : 'logo.' ) . $fileExtension;

				$param = array();
				// Путь к файлу-источнику большого изображения;
				$param['large_image_source'] = $aFileData['tmp_name'];
				// Оригинальное имя файла большого изображения
				$param['large_image_name'] = $aFileData['name'];

				// Путь к создаваемому файлу большого изображения;
				$param['large_image_target'] = $object->getPath() . $sImageName;

				// Использовать большое изображение для создания малого
				$param['create_small_image_from_large'] = FALSE;

				// Значение максимальной ширины большого изображения
				$param['large_image_max_width'] = Core_Array::getPost('large_max_width_image', 0);

				// Значение максимальной высоты большого изображения
				$param['large_image_max_height'] = Core_Array::getPost('large_max_height_image', 0);

				// Сохранять пропорции изображения для большого изображения
				$param['large_image_preserve_aspect_ratio'] = !is_null(Core_Array::getPost('large_preserve_aspect_ratio_image'));

				$object->createDir();

				$result = Core_File::adminUpload($param);

				if ($result['large_image'])
				{
					$object->image = $sImageName;
					$object->save();
				}
			}
			else
			{
				$this->addMessage(
					Core_Message::get(
						Core::_('Core.extension_does_not_allow', Core_File::getExtension($aFileData['name'])),
						'error'
					)
				);
			}
		}

		if (!$this->_object->siteuser_id)
		{
			$aObject_Directory_Emails = $this->_object->Directory_Emails->findAll(FALSE);
			$objectEmail = isset($aObject_Directory_Emails[0])
				? $aObject_Directory_Emails[0]->value
				: NULL;

			$oSiteuser = strlen((string) $objectEmail)
				? Core_Entity::factory('Siteuser')->getByEmail($objectEmail)
				: NULL;

			if (is_null($oSiteuser))
			{
				$oSiteuser = Core_Entity::factory('Siteuser');
				$oSiteuser->login = Core_Guid::get();
				$oSiteuser->email = $objectEmail;
				$oSiteuser->save();

				$oSiteuser->login = 'id' . $oSiteuser->id;
				$oSiteuser->save();
			}

			$this->_object->siteuser_id = $oSiteuser->id;
			$this->_object->save();

			$targetSelect = Core_Array::getGet('targetSelect', '', 'trim');

			if ($targetSelect != '')
			{
				$targetWindowId = preg_replace('/[^A-Za-z0-9_-]/', '', Core_Array::getGet('targetWindowId', '', 'str'));
				$sOperationName = $this->_Admin_Form_Controller->getOperation();

				if (in_array($sOperationName, array('saveModal', 'applyModal')) && strlen($targetWindowId) && strlen($targetSelect))
				{
					ob_start();
					Core_Html_Entity::factory('Script')
						->value('var objectSiteuserSelect = $("#' . $targetWindowId . ' select[name = ' . Core_Str::escapeJavascriptVariable($targetSelect) . ']");
							mainFormLocker.unlock();
							$.ajax({
								type: "GET",
								dataType: "json",
								url: hostcmsBackend + "/siteuser/index.php?'
									. ($modelName == 'siteuser_person' ? 'loadSiteuserPersonSelect2' : 'loadSiteuserCompanySelect2') . '=' . $this->_object->id . '"
							}).then(function (data) {
								if (data)
								{
									// create the option and append to Select2
									var option = new Option(data.text, data.id, true, true);
									objectSiteuserSelect.append(option).trigger("change");

									// manually trigger the `select2:select` event
									objectSiteuserSelect.trigger({
										type: "select2:select",
										params: {
											data: data
										}
									});
								}
							});')
						->execute();

					$sOperationName == 'saveModal' && $this->_Admin_Form_Controller->addMessage(ob_get_clean());
					$sOperationName == 'applyModal' && $this->_Admin_Form_Controller->addContent(ob_get_clean());
				}
			}
		}

		/*
		if (Core::moduleIsActive('search') && $object->indexing && $object->active)
		{
			Search_Controller::indexingSearchPages(array($object->indexing()));
		}
		*/

		Core_Event::notify(get_class($this) . '.onAfterRedeclaredApplyObjectProperty', $this, array($this->_Admin_Form_Controller));
	}
}
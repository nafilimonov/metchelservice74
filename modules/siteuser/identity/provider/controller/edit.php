<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Identity_Provider Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_Identity_Provider_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
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

		$this->addSkipColumn('image');

		parent::setObject($object);
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

		$oMainTab->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'));

		$this->title($this->_object->id
			? Core::_('Siteuser_Identity_Provider.form_edit_add_title_edit', $this->_object->name, FALSE)
			: Core::_('Siteuser_Identity_Provider.form_edit_add_title_add')
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

		$this->getField('url')
			->format(
				array(
					'maxlen' => array('value' => 255),
					'minlen' => array('value' => 1)
				)
			)
			->divAttr(array('class' => 'form-group col-xs-12 col-lg-6'));

		// Type
		$oMainTab->delete($this->getField('type'));

		$oType = Admin_Form_Entity::factory('Select')
			->name('type')
			->caption(Core::_('Siteuser_Identity_Provider.type'))
			->value($this->_object->type)
			->options(array(
				0 => 'OpenID',
				1 => 'OAuth'
			))
			->divAttr(array('class' => 'form-group col-xs-12 col-md-6 col-lg-3'));

		$oMainTab->addAfter($oType, $this->getField('url'));

		$this->getField('sorting')
			->divAttr(array('class' => 'form-group col-xs-12 col-md-6 col-lg-3'));

		$oMainTab->moveAfter($this->getField('sorting'), $oType);

		// Image
		// $oMainTab->delete($this->getField('image'));

		$oProviderFileField = Admin_Form_Entity::factory('File');

		$windowId = $this->_Admin_Form_Controller->getWindowId();

		$sFilePath = $this->_object->image != '' && Core_File::isFile($this->_object->getDirPath() . $this->_object->image)
			? '/' . $this->_object->getDirHref() . $this->_object->image
			: '';

		$sFormPath = $this->_Admin_Form_Controller->getPath();

		$oProviderFileField
			->type('file')
			->caption(Core::_('Siteuser_Identity_Provider.provider_files_uploaded'))
			->name('image')
			->id('image')
			->largeImage(
				array(
					'path' => $sFilePath,
					'show_params' => FALSE,
					'delete_onclick' => "$.adminLoad({path: '{$sFormPath}', additionalParams: 'hostcms[checked][{$this->_datasetId}][{$this->_object->id}]=1', action: 'deleteFile', windowId: '{$windowId}'}); return false",
					'delete_href' => ''
				)
			)
			->smallImage(
				array('show' => FALSE)
			)
			->divAttr(array('class' => 'input-group col-xs-12 col-sm-6'));

		$oMainRow1->add($oProviderFileField);

		$this->getField('icon')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'));

		return $this;
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @return self
	 * @hostcms-event Siteuser_Identity_Provider_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 */
	protected function _applyObjectProperty()
	{
		parent::_applyObjectProperty();

		if (
			// Поле файла существует
			!is_null($aFileData = Core_Array::getFiles('image', NULL))
			// и передан файл
			&& intval($aFileData['size']) > 0)
		{
			if (Core_File::isValidExtension($aFileData['name'], array('png')))
			{
				$this->_object->saveFile($aFileData['tmp_name'], $aFileData['name']);
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

		Core_Event::notify(get_class($this) . '.onAfterRedeclaredApplyObjectProperty', $this, array($this->_Admin_Form_Controller));
	}
}
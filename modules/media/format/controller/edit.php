<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Media Formats Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage Media
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Media_Format_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Set object
	 * @param object $object object
	 * @return self
	 */
	public function setObject($object)
	{
		$this->addSkipColumn('watermark_file');

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

		// Получаем стандартные вкладки
		$oMainTab = $this->getTab('main');

		$windowId = $this->_Admin_Form_Controller->getWindowId();

		$oMainTab
			->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow4 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow5 = Admin_Form_Entity::factory('Div')->class('row'))
		;

		$oMainTab
			->move($this->getField('name')->divAttr(array('class' => 'form-group col-xs-12')), $oMainRow1)
			->move($this->getField('width')->divAttr(array('class' => 'form-group col-xs-12 col-sm-5')), $oMainRow2)
			->move($this->getField('height')->divAttr(array('class' => 'form-group col-xs-12 col-sm-5')), $oMainRow2)
			;

		// Удаляем стандартный <input>
		$oMainTab->delete($this->getField('change_format'));

		$oChange_Format_Select = Admin_Form_Entity::factory('Select');
		$oChange_Format_Select
			->options(
				array(
					'' => '...',
					'webp' => 'WEBP',
					'png' => 'PNG',
					'jpg' => 'JPG'
				)
			)
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-2'))
			->name('change_format')
			->value($this->_object->change_format)
			->caption(Core::_('Media_Format.change_format'));

		$oMainRow2->add($oChange_Format_Select);

		$watermarkPath = $this->_object->watermark_file != '' && Core_File::isFile($this->_object->getWatermarkFilePath())
			? $this->_object->getWatermarkFileHref()
			: '';

		$sFormPath = $this->_Admin_Form_Controller->getPath();

		$oMainRow3->add(Admin_Form_Entity::factory('File')
			->type("file")
			->caption(Core::_('Media_Format.watermark_file'))
			->divAttr(array('class' => 'form-group col-xs-12'))
			->name("watermark_file")
			->id("watermark_file")
			->largeImage(
				array(
					'path' => $watermarkPath,
					'show_params' => FALSE,
					'delete_onclick' => "$.adminLoad({path: '{$sFormPath}', additionalParams: 'hostcms[checked][{$this->_datasetId}][{$this->_object->id}]=1', action: 'deleteWatermarkFile', windowId: '{$windowId}'}); return false",
				)
			)
			->smallImage(
				array(
					'show' => FALSE
				)
			));

		$oMainTab
			->move($this->getField('watermark_default_position_x')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6')), $oMainRow4)
			->move($this->getField('watermark_default_position_y')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6')), $oMainRow4)
			->move($this->getField('preserve_aspect_ratio')->divAttr(array('class' => 'form-group col-xs-12')), $oMainRow5);

		$this->title($this->_object->id
			? Core::_('Media_Format.edit_form_title', $this->_object->name)
			: Core::_('Media_Format.add_form_title')
		);

		return $this;
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @hostcms-event Media_Format_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 */
	protected function _applyObjectProperty()
	{
		parent::_applyObjectProperty();

		if (
			// Поле файла существует
			!is_null($aFileData = Core_Array::getFiles('watermark_file', NULL))
			// и передан файл
			&& intval($aFileData['size']) > 0)
		{
			if (Core_File::isValidExtension($aFileData['name'], array('png')))
			{
				$this->_object->saveWatermarkFile($aFileData['tmp_name']);
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
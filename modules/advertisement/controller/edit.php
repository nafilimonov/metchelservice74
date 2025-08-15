<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Advertisement Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage Advertisement
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Advertisement_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Prepare backend item's edit form
	 *
	 * @return self
	 */
	protected function _prepareForm()
	{
		parent::_prepareForm();

		$this->title(
			$this->_object->id
				? Core::_('Advertisement.edit_title', $this->_object->name, FALSE)
				: Core::_('Advertisement.add_title')
		);

		$oMainTab = $this->getTab('main');
		$oAdditionalTab = $this->getTab('additional');

		$oMainTab
			->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow4 = Admin_Form_Entity::factory('Div')->class('row hidden-1 hidden-2'))
			->add($oMainRow5 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow6 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow7 = Admin_Form_Entity::factory('Div')->class('row'));

		$windowId = $this->_Admin_Form_Controller->getWindowId();

		$oAdmin_Form_Entity_Select_Type = Admin_Form_Entity::factory('Select');
		$oAdmin_Form_Entity_Select_Type
			->options(
				array(
					Core::_('Advertisement.type_image'),
					Core::_('Advertisement.type_html'),
					Core::_('Advertisement.type_popup'),
					Core::_('Advertisement.type_flash')
				)
			)
			->name('type')
			->value($this->_object->type)
			->caption(Core::_('Advertisement.type'))
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'))
			->onchange("radiogroupOnChange('{$windowId}', $(this).val(), [0,1,2,3])");

		// Удаляем стандартный <input> для "Тип баннера"
		$oMainTab->delete($this->getField('type'));

		// Контроллер редактирования структуры
		$Structure_Controller_Edit = new Structure_Controller_Edit($this->_Admin_Form_Action);
		$aStructureList = $Structure_Controller_Edit->fillStructureList($this->_object->site_id);

		// <select> со списком структуры
		$oAdmin_Form_Entity_Select_Structure = Admin_Form_Entity::factory('Select');
		$oAdmin_Form_Entity_Select_Structure
			->options(
				array(' … ') + $aStructureList
			)
			->name('structure_id')
			->value($this->_object->structure_id)
			->caption(Core::_('Advertisement.structure_id'))
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'));

		// Удаляем стандартный <input> для "Узла структуры"
		$oAdditionalTab->delete($this->getField('structure_id'));

		$oMainRow1
			->add($oAdmin_Form_Entity_Select_Type)
			->add($oAdmin_Form_Entity_Select_Structure);

		$oMainTab->move($this->getField('description'), $oMainRow2);

		$oMainTab->delete($this->getField('showed_today'));

		$this->getField('showed')
			->disabled('disabled')
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'));

		$this->getField('showed_today')
			->disabled('disabled')
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'));

		$oMainTab->move($this->getField('showed'), $oMainRow3);
		$oMainRow3->add($this->getField('showed_today'));

		$sFilePath = Core_File::isFile($this->_object->getFilePath())
			? $this->_object->getFileHref()
			: '';

		$sFormPath = $this->_Admin_Form_Controller->getPath();

		// <input> загрузки файла баннера (изображение/flash)
		$oAdmin_Form_Entity_File = Admin_Form_Entity::factory('File')
			->type('file')
			->name('source')
			->caption(Core::_('Advertisement.source'))
			->largeImage(
				array(
					'path' => $sFilePath,
					'show_params' => FALSE,
					'delete_onclick' => "$.adminLoad({path: '{$sFormPath}', additionalParams: 'hostcms[checked][{$this->_datasetId}][{$this->_object->id}]=1', action: 'deleteFile', windowId: '{$windowId}'}); return false",
					'delete_href' => ''
				)
			)->smallImage(
				array('show' => FALSE)
			)
			// ->divAttr(array('class' => 'input-group col-xs-12 col-md-6 hidden-1 hidden-2'))
			;

		// Удаляем стандартный <input> для "Тип баннера"
		$oMainTab->delete($this->getField('source'));

		$oMainRow4->add($oAdmin_Form_Entity_File);

		$this->getField('href')->divAttr(array('class' => 'form-group col-xs-12 hidden-1 hidden-2'));
		$oMainTab->move($this->getField('href'), $oMainRow5);

		$this->getField('html')->divAttr(array('class' => 'form-group col-xs-12 hidden-0 hidden-2 hidden-3'));
		$oMainTab->move($this->getField('html'), $oMainRow6);

		// <select> со списком структуры для всплывающих баннеров
		$oAdmin_Form_Entity_Select_Popup = Admin_Form_Entity::factory('Select');
		$oAdmin_Form_Entity_Select_Popup
			->options(
				array(' … ') + $aStructureList
			)
			->name('popup_structure_id')
			->value($this->_object->popup_structure_id)
			->caption(Core::_('Advertisement.popup_structure_id'))
			->divAttr(array('class' => 'form-group col-xs-12 hidden-0 hidden-1 hidden-3'));

		$oAdditionalTab->delete($this->getField('popup_structure_id'));
		$oMainRow7->add($oAdmin_Form_Entity_Select_Popup);

		// Удаляем отображение текущей даты
		$oMainTab->delete($this->getField('last_date'));

		// Удаляем отображение общего количества показов
		$oMainTab->delete($this->getField('clicks'));

		// Закладка размеров баннера
		$oAdmin_Form_Tab_Entity_Lib_Sizes = Admin_Form_Entity::factory('Tab')
			->caption(Core::_('Advertisement.sizes_tab'))
			->name('sizes_tab');

		$this->addTabAfter($oAdmin_Form_Tab_Entity_Lib_Sizes, $oMainTab);

		$oAdmin_Form_Tab_Entity_Lib_Sizes
			->add($oSizesRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oSizesRow2 = Admin_Form_Entity::factory('Div')->class('row'));

		$this->getField('width')->divAttr(array('class' => 'form-group col-lg-3 col-md-4 col-sm-5'));
		$this->getField('height')->divAttr(array('class' => 'form-group col-lg-3 col-md-4 col-sm-5'));

		$oMainTab
			->move($this->getField('width'), $oSizesRow1)
			->move($this->getField('height'), $oSizesRow2);

		// Закладка условий показа баннера
		$oAdmin_Form_Tab_Entity_Lib_Shows = Admin_Form_Entity::factory('Tab')
			->caption(Core::_('Advertisement.shows_tab'))
			->name('shows_tab');

		$this->addTabAfter($oAdmin_Form_Tab_Entity_Lib_Shows, $oMainTab);

		$oAdmin_Form_Tab_Entity_Lib_Shows
			->add($oShowsRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oShowsRow2 = Admin_Form_Entity::factory('Div')->class('row'));

		$this->getField('show_total')
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'));
		$this->getField('show_per_day')
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'));
		$this->getField('start_datetime')
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'));
		$this->getField('end_datetime')
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'));

		$oMainTab
			->move($this->getField('show_total'), $oShowsRow1)
			->move($this->getField('show_per_day'), $oShowsRow1)
			->move($this->getField('start_datetime'), $oShowsRow2)
			->move($this->getField('end_datetime'), $oShowsRow2);

		$oAdmin_Form_Entity_Code = Admin_Form_Entity::factory('Code');
		$oAdmin_Form_Entity_Code->html(
			"<script>radiogroupOnChange('{$windowId}', " . intval($this->_object->type) . ", [0,1,2,3])</script>"
		);

		$oMainTab->add($oAdmin_Form_Entity_Code);

		return $this;
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @hostcms-event Advertisement_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 */
	protected function _applyObjectProperty()
	{
		$this
			->addSkipColumn('last_date')
			->addSkipColumn('showed_today')
			->addSkipColumn('showed')
			->addSkipColumn('clicks')
			->addSkipColumn('source');

		parent::_applyObjectProperty();

		if (
			// Поле файла существует
			!is_null($aFileData = Core_Array::getFiles('source', NULL))
			// и передан файл
			&& intval($aFileData['size']) > 0)
		{
			if (Core_File::isValidExtension($aFileData['name'], array('jpg', 'jpeg', 'gif', 'png', 'webp', 'swf')))
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
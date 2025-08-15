<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Form_Fill Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage Form
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Form_Fill_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Tab
	 * @var Skin_Default_Admin_Form_Entity_Tab
	 */
	protected $_tab = NULL;

	/**
	 * Set object
	 * @param object $object object
	 * @return self
	 */
	public function setObject($object)
	{
		if (!$object->read)
		{
			$object->read = 1;
			$object->save();
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

		$this->_tab = $oMainTab;

		// Order tags
		if ($this->_object->source_id)
		{
			$this->addTabAfter(
				$oTagTab = Admin_Form_Entity::factory('Tab')
					->caption(Core::_('Form_Fill.tab2'))
					->name('Tags'), $oMainTab
			);

			$oSource = $this->_object->Source;

			$oTagTab
				->add($oTagRow1 = Admin_Form_Entity::factory('Div')->class('row'));

			$aSourceFields = array('service', 'campaign', 'ad', 'source', 'medium', 'content', 'term');

			foreach ($aSourceFields as $sFieldName)
			{
				$oAdmin_Form_Entity_Input = Admin_Form_Entity::factory('Input')
					->name('source_' . $sFieldName)
					->divAttr(array('class' => 'form-group col-xs-4 col-sm-4'))
					->caption(Core::_('Source.' . $sFieldName))
					->class('form-control input-group-input')
					->value($oSource->$sFieldName);

				$oTagRow1->add($oAdmin_Form_Entity_Input);
			}
		}

		$oMainTab
			->moveAfter($this->getField('ip'), $this->getField('form_id'), $oAdditionalTab)
			->moveAfter($this->getField('read'), $this->getField('ip'), $oAdditionalTab);

		$this->_setFormFieldDirs(0, $this->_tab);

		$oMainTab
			->move($this->getField('datetime')->divAttr(array('class' => 'form-group col-xs-12 col-md-3')), $oMainRow1);

		$oAdditionalTab->delete($this->getField('form_status_id'));

		$aMasFormStatuses = array(array('value' => Core::_('Admin.none'), 'color' => '#aebec4'));

		$aForm_Statuses = Core_Entity::factory('Form_Status')->findAll();
		foreach ($aForm_Statuses as $oForm_Status)
		{
			$aMasFormStatuses[$oForm_Status->id] = array(
				'value' => $oForm_Status->name,
				'color' => $oForm_Status->color
			);
		}

		$oDropdownlistFormStatuses = Admin_Form_Entity::factory('Dropdownlist')
			->options($aMasFormStatuses)
			->name('form_status_id')
			->value($this->_object->form_status_id)
			->caption(Core::_('Form_Fill.form_status_id'))
			->divAttr(array('class' => 'form-group col-xs-12 col-md-9'));

		$oMainRow1->add($oDropdownlistFormStatuses);

		$this->title($this->_object->id
			? Core::_('Form_Fill.filled_title', $this->_object->Form->name, FALSE)
			: Core::_('Form_Fill.filled_title', $this->_object->Form->name, FALSE)
		);

		return $this;
	}

	/**
	 * Add external form field dir container to $parentObject
	 * @param int $parent_id ID of parent directory of form field dirs
	 * @param object $parentObject
	 */
	protected function _setFormFieldDirs($parent_id, $parentObject)
	{
		$windowId = $this->_Admin_Form_Controller->getWindowId();

		$oAdmin_Form_Entity_Section = Admin_Form_Entity::factory('Section')
			->caption($parent_id == 0
				? Core::_('Form_Field_Dir.main_section')
				: htmlspecialchars(Core_Entity::factory('Form_Field_Dir', $parent_id)->name)
			)
			->id('accordion_' . $parent_id);

		$oForm_Fill_Fields = $this->_object->Form_Fill_Fields;
		$oForm_Fill_Fields
			->queryBuilder()
			->select('form_fill_fields.*')
			->join('form_fields', 'form_fill_fields.form_field_id', '=', 'form_fields.id')
			->where('form_fields.form_field_dir_id', '=', $parent_id);

		$aForm_Fill_Fields = $oForm_Fill_Fields->findAll(FALSE);

		foreach ($aForm_Fill_Fields as $oForm_Fill_Field)
		{
			switch ($oForm_Fill_Field->Form_Field->type)
			{
				case 0: // Текстовое поле
				case 1: // Поле пароля
				case 3: // Радиокнопка
				case 6: // Список
				case 7: // Скрытое поле
				case 9: // Список из флажков
				default:
					$oInput_Item = Admin_Form_Entity::factory('Input');
					$oInput_Item
						->name('form_fill_field_' . $oForm_Fill_Field->Form_Field->id . '_' . $oForm_Fill_Field->id)
						->value($oForm_Fill_Field->value)
						->caption(htmlspecialchars($oForm_Fill_Field->Form_Field->caption));
				break;

				case 2: // Поле загрузки файла
					$oInput_Item = Admin_Form_Entity::factory('Div')
						->id('input_item_' . $oForm_Fill_Field->id)
						->class('form-group col-xs-12')
						->style('position: relative');

					$oFileDiv = Core_Html_Entity::factory('Div')
						->id('file_' . $oForm_Fill_Field->id)
						->class('file-caption');

					$oFileDiv->add(Core_Html_Entity::factory('I')
						->class(Core_File::getIcon($oForm_Fill_Field->value) . ' margin-right-5')
					);

					$href = Admin_Form_Controller::correctBackendPath('/{admin}/form/fill/index.php?downloadFile=') . $oForm_Fill_Field->id;


					$oFileDiv->add(
						Core_Html_Entity::factory('A')
							->href($href)
							->target('_blank')
							->value(htmlspecialchars($oForm_Fill_Field->value))
						);

					$attachments_size = Core_File::filesize($oForm_Fill_Field->getPath());

					$textSize = Core_Str::getTextSize($attachments_size);

					$oFileDiv->add(Core_Html_Entity::factory('Span')
						->value("({$textSize})")
					);

					$oInput_Item
						->add(
							// <span class="caption">Скрытое</span>
							Core_Html_Entity::factory('Span')
								->class('caption')
								->value(htmlspecialchars($oForm_Fill_Field->Form_Field->caption))
						)
						->add($oFileDiv);
				break;

				case 4: // Флажок
					$oInput_Item_Checkbox = Admin_Form_Entity::factory('Checkbox')
						->name('form_fill_field_' . $oForm_Fill_Field->Form_Field->id . '_' . $oForm_Fill_Field->id)
						->checked(NULL)
						->caption(htmlspecialchars($oForm_Fill_Field->Form_Field->caption));

					$oForm_Fill_Field->value && $oInput_Item_Checkbox->checked('checked');

					$oInput_Item = Admin_Form_Entity::factory('Div')
						->class('form-group col-xs-12')
						->add($oInput_Item_Checkbox);

				break;

				case 5: // Большое текстовое поле
					// $oInput_Item_Textarea = Admin_Form_Entity::factory('Textarea');

					/*$oInput_Item = Admin_Form_Entity::factory('Div')
						->class('form-group col-xs-12')
						->add($oInput_Item_Textarea);*/

					$oInput_Item = Admin_Form_Entity::factory('Textarea')
						->name('form_fill_field_' . $oForm_Fill_Field->Form_Field->id . '_' . $oForm_Fill_Field->id)
						->value($oForm_Fill_Field->value)
						->caption(htmlspecialchars($oForm_Fill_Field->Form_Field->caption));
				break;

				case 8: // Надпись
					continue 2;
			}

			$oAdmin_Form_Entity_Section->add(
				Admin_Form_Entity::factory('Div')
					->class('row')
					->add($oInput_Item)
			);

			if ($oForm_Fill_Field->Form_Field->type == 2)
			{
				$path = $oForm_Fill_Field->getPath();

				if (Core_File::isValidExtension($path, Admin_Form_Entity::factory('File')->getPreviewExtensions()))
				{
					$prefixRand = strpos($path, '?') === FALSE ? '?' : '&';

					$style = Core_File::getExtension($path) === 'svg'
						? 'height:100px'
						: 'max-height:200px';

					$oAdmin_Form_Entity_Section->add(Core_Html_Entity::factory('Script')
						->value('$(function(){
							$("#' . $windowId . ' #file_' . $oForm_Fill_Field->id . '").popover({
								content: \'<img src="' . $href . $prefixRand . 'rnd=' . rand() .'" style="' . $style . '" />\',
								html: true,
								placement: "top",
								container: "body",
								container: "#' . $windowId . ' #input_item_' . $oForm_Fill_Field->id . '",
								trigger: "hover"
							});
						});'
					));
				}
			}
		}

		// Form Field Dirs
		$oForm_Field_Dirs = Core_Entity::factory('Form_Field_Dir');
		$oForm_Field_Dirs
			->queryBuilder()
			->where('parent_id', '=', $parent_id);

		$aForm_Field_Dirs = $oForm_Field_Dirs->findAll(FALSE);
		foreach ($aForm_Field_Dirs as $oForm_Field_Dir)
		{
			$this->_setFormFieldDirs($oForm_Field_Dir->id, $parent_id == 0 ? $this->_tab : $oAdmin_Form_Entity_Section);
		}

		$oAdmin_Form_Entity_Section->getCountChildren() && $parentObject->add($oAdmin_Form_Entity_Section);
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @return self
	 * @hostcms-event Form_Fill_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 */
	protected function _applyObjectProperty()
	{
		parent::_applyObjectProperty();

		$aForm_Fill_Fields = $this->_object->Form_Fill_Fields->findAll();

		foreach ($aForm_Fill_Fields as $oForm_Fill_Field)
		{
			$value = Core_Array::getPost('form_fill_field_' . $oForm_Fill_Field->Form_Field->id . '_' . $oForm_Fill_Field->id);

			switch ($oForm_Fill_Field->Form_Field->type)
			{
				case 0: // Текстовое поле
				case 1: // Поле пароля
				case 3: // Радиокнопка
				case 6: // Список
				case 7: // Скрытое поле
				case 9: // Список из флажков
				case 5: // Большое текстовое поле
				default:
					!is_null($value) && $oForm_Fill_Field->value($value)->save();
				break;
				case 4: // Флажок
					$oForm_Fill_Field->value(is_null($value) ? 0 : 1)->save();
				break;
				case 2: // Поле загрузки файла
					//
				break;
				case 8: //Надпись
				break;
			}
		}

		Core_Event::notify(get_class($this) . '.onAfterRedeclaredApplyObjectProperty', $this, array($this->_Admin_Form_Controller));

		return $this;
	}
}
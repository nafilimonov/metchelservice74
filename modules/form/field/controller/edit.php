<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Form_Field Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage Form
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Form_Field_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Set object
	 * @param object $object object
	 * @return self
	 */
	public function setObject($object)
	{
		$form_id = Core_Array::getGet('form_id', 0);
		$form_field_dir_id = Core_Array::getGet('form_field_dir_id', 0);
		$oForm = Core_Entity::factory('Form', $form_id);

		switch ($object->getModelName())
		{
			case 'form_field':

				$title = $object->id
					? Core::_('Form_Field.edit_title', $object->name, FALSE)
					: Core::_('Form_Field.add_title', $oForm->name, FALSE);

				if (!$object->id)
				{
					$object->form_id = $form_id;
					$object->form_field_dir_id = $form_field_dir_id;
				}

				parent::setObject($object);

				$oMainTab = $this->getTab('main');
				$oAdditionalTab = $this->getTab('additional');

				$oMainTab
					->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oMainRow4 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oMainRow5 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oMainRow6 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oMainRow7 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oMainRow8 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oMainRow9 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oMainRow10 = Admin_Form_Entity::factory('Div')->class('row'));

				$this->getField('caption')->rows(2);

				$oMainTab
					->move($this->getField('caption'), $oMainRow1);

				$oMainTab->delete($this->getField('type'));

				$windowId = $this->_Admin_Form_Controller->getWindowId();

				$oMainTab->add(
					Admin_Form_Entity::factory('Code')->html(
						"<script>radiogroupOnChange('{$windowId}', " . intval($this->_object->type) . ", [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19])</script>"
					)
				);

				$oAdditionalTab->delete($this->getField('form_field_dir_id'));

				$oMainRow2->add(
					Admin_Form_Entity::factory('Select')
						->caption(Core::_('Form_Field_Dir.parent_id'))
						->options(array(' … ') + $this->fillGroup($this->_object->form_id))
						->name('form_field_dir_id')
						->value($this->_object->form_field_dir_id)
						->filter(TRUE)
					);

				$oMainTab->move($this->getField('description'), $oMainRow3);

				// Селектор с типом
				$oSelect_Type = Admin_Form_Entity::factory('Select')
					->options(array(
						Core::_('Form_Field.type0'),
						Core::_('Form_Field.type1'),
						Core::_('Form_Field.type2'),
						Core::_('Form_Field.type3'),
						Core::_('Form_Field.type4'),
						Core::_('Form_Field.type5'),
						Core::_('Form_Field.type6'),
						Core::_('Form_Field.type7'),
						Core::_('Form_Field.type8'),
						Core::_('Form_Field.type9'),
						Core::_('Form_Field.type10'),
						Core::_('Form_Field.type11'),
						Core::_('Form_Field.type12'),
						Core::_('Form_Field.type13'),
						Core::_('Form_Field.type14'),
						Core::_('Form_Field.type15'),
						Core::_('Form_Field.type16'),
						Core::_('Form_Field.type17'),
						Core::_('Form_Field.type18'),
						Core::_('Form_Field.type19'),
					))
					->name('type')
					->value($this->_object->type)
					->caption(Core::_('Form_Field.type'))
					->onchange("radiogroupOnChange('{$windowId}', $(this).val(), [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19])")
					->divAttr(array('class' => 'form-group col-xs-12 col-md-6'));

				$oMainRow4->add($oSelect_Type);

				$this->getField('size')
					->divAttr(array('class' => 'form-group col-xs-12 col-md-6 hidden-3 hidden-4 hidden-5 hidden-6 hidden-7 hidden-8 hidden-9'));
				$oMainTab
					->move($this->getField('size'), $oMainRow4);

				// Список
				if (Core::moduleIsActive('list'))
				{
					$oAdditionalTab->delete($this->getField('list_id'));

					$oList_Controller_Edit = new List_Controller_Edit($this->_Admin_Form_Action);

					$oMainRow4->add(
						Admin_Form_Entity::factory('Select')
							->options(
								$oList_Controller_Edit->fillLists(CURRENT_SITE)
							)
							->name('list_id')
							->value($this->_object->list_id)
							->caption(Core::_('Form_Field.list_id'))
							->divAttr(array('class' => 'form-group col-xs-12 col-md-6 hidden-0 hidden-1 hidden-2 hidden-4 hidden-5 hidden-7 hidden-8 hidden-10 hidden-11 hidden-12 hidden-13 hidden-14 hidden-15 hidden-16 hidden-17 hidden-18 hidden-19'))
					);
				}

				$this->getField('name')
					->divAttr(array('class' => 'form-group col-xs-12 col-md-3'));
				$oMainTab->move($this->getField('name'), $oMainRow5);

				$this->getField('placeholder')
					->divAttr(array('class' => 'form-group col-xs-12 col-md-3'));
				$oMainTab->move($this->getField('placeholder'), $oMainRow5);

				$this->getField('sorting')->divAttr(array('class' => 'form-group col-xs-12 col-md-6'));

				$oMainTab
					->move($this->getField('sorting'), $oMainRow5);

				$this->getField('checked')
					->divAttr(array('class' => 'form-group col-xs-12 col-md-6 hidden-0 hidden-1 hidden-2 hidden-3 hidden-5 hidden-6 hidden-7 hidden-8 hidden-9 hidden-10 hidden-11 hidden-12 hidden-13 hidden-14 hidden-15 hidden-16 hidden-17 hidden-18 hidden-19'));

				$oMainTab
					->move($this->getField('checked'), $oMainRow6);

				$this->getField('rows')
					->divAttr(array('class' => 'form-group col-xs-12 col-md-6 hidden-0 hidden-1 hidden-2 hidden-3 hidden-4 hidden-6 hidden-7 hidden-8 hidden-9 hidden-10 hidden-11 hidden-12 hidden-13 hidden-14 hidden-15 hidden-16 hidden-17 hidden-18 hidden-19'));

				$this->getField('cols')
					->divAttr(array('class' => 'form-group col-xs-12 col-md-6 hidden-0 hidden-1 hidden-2 hidden-3 hidden-4 hidden-6 hidden-7 hidden-8 hidden-9 hidden-10 hidden-11 hidden-12 hidden-13 hidden-14 hidden-15 hidden-16 hidden-17 hidden-18 hidden-19'));

				$oMainTab
					->move($this->getField('cols'), $oMainRow7)
					->move($this->getField('rows'), $oMainRow7);

				$this->getField('default_value')
					->rows(2)
					->divAttr(array('class' => 'form-group col-xs-12 hidden-2 hidden-3 hidden-4 hidden-6 hidden-9'));

				$oMainTab
					->move($this->getField('default_value'), $oMainRow8);

				$this->getField('obligatory')->divAttr(array('class' => 'form-group col-lg-4 hidden-9'));
				$oMainTab
					->move($this->getField('obligatory'), $oMainRow9);

				$oMainTab->move($this->getField('active')->divAttr(array('class' => 'form-group col-lg-4')), $oMainRow10);

			break;
			case 'form_field_dir':
				$title = $object->id
					? Core::_('Form_Field_Dir.edit_title', $oForm->name, FALSE)
					: Core::_('Form_Field_Dir.add_title', $oForm->name, FALSE);

				if (!$object->id)
				{
					$object->form_id = $form_id;
					$object->parent_id = $form_field_dir_id;
				}
				parent::setObject($object);

				$oMainTab = $this->getTab('main');
				$oAdditionalTab = $this->getTab('additional');

				$oAdditionalTab->delete($this->getField('parent_id'));

				$oSelect = Admin_Form_Entity::factory('Select')
					->caption(Core::_('Form_Field_Dir.parent_id'))
					->options(array(' … ') + $this->fillGroup($this->_object->form_id, 0, array($this->_object->id)))
					->name('parent_id')
					->value($this->_object->parent_id)
					->filter(TRUE);

				$oMainTab->addAfter($oSelect, $this->getField('name'));

			break;
		}

		$this->title($title);
		return $this;
	}

	/**
	 * Groups tree
	 * @var array
	 */
	protected $_aGroupTree = array();

	/**
	 * Build visual representation of group tree
	 * @param int $iFormId form ID
	 * @param int $iParentId parent ID
	 * @param array $aExclude exclude group ID
	 * @param int $iLevel current nesting level
	 * @return array
	 */
	public function fillGroup($iFormId, $iParentId = 0, $aExclude = array(), $iLevel = 0)
	{
		$iFormId = intval($iFormId);
		$iParentId = intval($iParentId);
		$iLevel = intval($iLevel);

		if ($iLevel == 0)
		{
			$aTmp = Core_QueryBuilder::select('id', 'parent_id', 'name')
				->from('form_field_dirs')
				->where('form_id', '=', $iFormId)
				->where('deleted', '=', 0)
				->orderBy('sorting')
				->orderBy('name')
				->execute()->asAssoc()->result();

			foreach ($aTmp as $aGroup)
			{
				$this->_aGroupTree[$aGroup['parent_id']][] = $aGroup;
			}
		}

		$aReturn = array();

		if (isset($this->_aGroupTree[$iParentId]))
		{
			$countExclude = count($aExclude);
			foreach ($this->_aGroupTree[$iParentId] as $childrenGroup)
			{
				if ($countExclude == 0 || !in_array($childrenGroup['id'], $aExclude))
				{
					$aReturn[$childrenGroup['id']] = str_repeat('  ', $iLevel) . $childrenGroup['name'];
					$aReturn += $this->fillGroup($iFormId, $childrenGroup['id'], $aExclude, $iLevel + 1);
				}
			}
		}

		$iLevel == 0 && $this->_aGroupTree = array();

		return $aReturn;
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @return self
	 * @hostcms-event Form_Field_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 */
	protected function _applyObjectProperty()
	{
		if (isset($this->_formValues['name']) && $this->_formValues['name'] === '')
		{
			$this->_formValues['name'] = Core_Str::transliteration($this->_formValues['caption']);
		}

		parent::_applyObjectProperty();

		Core_Event::notify(get_class($this) . '.onAfterRedeclaredApplyObjectProperty', $this, array($this->_Admin_Form_Controller));

		return $this;
	}
}
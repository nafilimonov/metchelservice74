<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Shortlink Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage Shortlink
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Shortlink_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Set object
	 * @param object $object object
	 * @return self
	 */
	public function setObject($object)
	{
		$modelName = $object->getModelName();

		switch ($modelName)
		{
			case 'shortlink':
				if (!$object->id)
				{
					$object->shortlink_dir_id = Core_Array::getGet('shortlink_dir_id', 0);
				}

				parent::setObject($object);

				// Получаем стандартные вкладки
				$oMainTab = $this->getTab('main');
				$oAdditionalTab = $this->getTab('additional');

				$oMainTab
					->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'))
				;

				$oMainTab
					->move($this->getField('source')->placeholder(Core::_('Shortlink.source-placeholder'))->divAttr(array('class' => 'form-group col-xs-12')), $oMainRow1)
					->move($this->getField('shortlink')->placeholder(Core::_('Shortlink.shortlink-placeholder'))->divAttr(array('class' => 'form-group col-xs-12 col-sm-8')), $oMainRow2)
					->move($this->getField('description')->divAttr(array('class' => 'form-group col-xs-12')), $oMainRow1);

				// Удаляем тип
				$oMainTab->delete($this->getField('type'));

				$oTypeSelect = Admin_Form_Entity::factory('Select');

				$oTypeSelect
					->class('form-control')
					->caption(Core::_('Shortlink.type'))
					->options(array (
							301 => Core::_('Shortlink.301'),
							302 => Core::_('Shortlink.302'),
							303 => Core::_('Shortlink.303'),
							307 => Core::_('Shortlink.307')
						))
					->name('type')
					->value($this->_object->type)
					->divAttr(array('class' => 'form-group col-xs-12 col-sm-4'));

				$oMainRow2->add($oTypeSelect);

				// Удаляем группу
				$oAdditionalTab->delete($this->getField('shortlink_dir_id'));

				$oGroupSelect = Admin_Form_Entity::factory('Select');

				$oGroupSelect
					->caption(Core::_('Shortlink.shortlink_dir_id'))
					->options(array(' … ') + self::fillShortlinkDir())
					->name('shortlink_dir_id')
					->value($this->_object->shortlink_dir_id)
					->divAttr(array('class' => 'form-group col-xs-12 col-md-3'));

				$oMainRow3->add($oGroupSelect);

				$oMainTab
					->move($this->getField('hits')->readonly('readonly')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 col-md-2')), $oMainRow3)
					->move($this->getField('datetime')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 col-md-4 col-lg-3')), $oMainRow3)
					->move($this->getField('active')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 col-md-2 margin-top-21')), $oMainRow3)
					->move($this->getField('log')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 col-md-2 margin-top-21')), $oMainRow3);

				$title = $this->_object->id
					? Core::_('Shortlink.edit_form_title')
					: Core::_('Shortlink.add_form_title');
			break;

			case 'shortlink_dir':
				if (!$object->id)
				{
					$object->parent_id = Core_Array::getGet('shortlink_dir_id', 0);
				}

				parent::setObject($object);

				// Получаем стандартные вкладки
				$oMainTab = $this->getTab('main');
				$oAdditionalTab = $this->getTab('additional');

				$oMainTab
					->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'))
					;

				$oMainTab
					->move($this->getField('name')->divAttr(array('class' => 'form-group col-xs-12')), $oMainRow1)
					->move($this->getField('description')->divAttr(array('class' => 'form-group col-xs-12')), $oMainRow2);

				// Удаляем группу
				$oAdditionalTab->delete($this->getField('parent_id'));

				$oGroupSelect = Admin_Form_Entity::factory('Select')
					->caption(Core::_('Shortlink_Dir.parent_id'))
					->options(array(' … ') + self::fillShortlinkDir(0, array($this->_object->id)))
					->name('parent_id')
					->value($this->_object->parent_id)
					->divAttr(array('class' => 'form-group col-xs-12 col-md-6'));

				$oMainRow3->add($oGroupSelect);

				$oMainTab
					->move($this->getField('sorting')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 col-md-3')), $oMainRow3);

				$title = $this->_object->id
					? Core::_('Shortlink_Dir.edit_form_title', $this->_object->name, FALSE)
					: Core::_('Shortlink_Dir.add_form_title');
			break;
		}

		$this->title($title);

		return $this;
	}

	/**
	 * Redirect groups tree
	 * @var array
	 */
	static protected $_aGroupTree = array();

	/**
	 * Build visual representation of group tree
	 * @param int $iShortlinkDirParentId parent ID
	 * @param array $aExclude exclude group ID
	 * @param int $iLevel current nesting level
	 * @return array
	 */
	static public function fillShortlinkDir($iShortlinkDirParentId = 0, $aExclude = array(), $iLevel = 0)
	{
		$iShortlinkDirParentId = intval($iShortlinkDirParentId);
		$iLevel = intval($iLevel);

		if ($iLevel == 0)
		{
			$aTmp = Core_QueryBuilder::select('id', 'parent_id', 'name')
				->from('shortlink_dirs')
				->where('deleted', '=', 0)
				->orderBy('sorting')
				->orderBy('name')
				->execute()->asAssoc()->result();

			foreach ($aTmp as $aGroup)
			{
				self::$_aGroupTree[$aGroup['parent_id']][] = $aGroup;
			}
		}

		$aReturn = array();

		if (isset(self::$_aGroupTree[$iShortlinkDirParentId]))
		{
			$countExclude = count($aExclude);
			foreach (self::$_aGroupTree[$iShortlinkDirParentId] as $childrenGroup)
			{
				if ($countExclude == 0 || !in_array($childrenGroup['id'], $aExclude))
				{
					$aReturn[$childrenGroup['id']] = str_repeat('  ', $iLevel) . $childrenGroup['name'] . ' [' . $childrenGroup['id'] . ']' ;
					$aReturn += self::fillShortlinkDir($childrenGroup['id'], $aExclude, $iLevel + 1);
				}
			}
		}

		$iLevel == 0 && self::$_aGroupTree = array();

		return $aReturn;
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @hostcms-event Shortlink_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 */
	protected function _applyObjectProperty()
	{
		$modelName = $this->_object->getModelName();

		if ($modelName == 'shortlink')
		{
			$this->_formValues['source'] = Core_Array::get($this->_formValues, 'source', '', 'trim');
			$this->_formValues['shortlink'] = Core_Array::get($this->_formValues, 'shortlink', '', 'trim');
		}

		parent::_applyObjectProperty();

		switch ($modelName)
		{
			case 'shortlink':
				if (!strlen($this->_object->shortlink))
				{
					$this->_object->generateShortlink();
				}
			break;
		}

		Core_Event::notify(get_class($this) . '.onAfterRedeclaredApplyObjectProperty', $this, array($this->_Admin_Form_Controller));

		return $this;
	}
}
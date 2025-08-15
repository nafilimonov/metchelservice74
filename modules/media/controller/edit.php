<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Media Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage Media
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Media_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Set object
	 * @param object $object object
	 * @return self
	 */
	public function setObject($object)
	{
		$this
			->addSkipColumn('file')
			->addSkipColumn('width')
			->addSkipColumn('height')
			->addSkipColumn('size')
			->addSkipColumn('type')
			;

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

		$modalWindowId = preg_replace('/[^A-Za-z0-9_-]/', '', Core_Array::getGet('modalWindowId', '', 'str'));
		$modalWindowId && $this->_Admin_Form_Controller->windowId($modalWindowId);

		$object = $this->_object;

		$modelName = $object->getModelName();

		switch ($modelName)
		{
			case 'media_item':
				if (!$object->id)
				{
					$object->media_group_id = Core_Array::getGet('media_group_id', 0);
				}

				parent::setObject($object);

				$windowId = $this->_Admin_Form_Controller->getWindowId();

				$title = $this->_object->id
					? Core::_('Media_Item.edit_form_title', $this->_object->name, FALSE)
					: Core::_('Media_Item.add_form_title');

				$oMainTab = $this->getTab('main');
				$oAdditionalTab = $this->getTab('additional');

				$oMainTab
					->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oMainRow4 = Admin_Form_Entity::factory('Div')->class('row'))
					->add($oMainRow5 = Admin_Form_Entity::factory('Div')->class('row'));

				$type = Media_Controller::getType($this->_object);

				if ($type == 1)
				{
					$oMainTab->add($oMediaFromatsBlock = Admin_Form_Entity::factory('Div')->class('well with-header media-formats'));

					$oMediaFromatsBlock
						->add(Admin_Form_Entity::factory('Div')
							->class('header bordered-palegreen')
							->value(Core::_('Media_Item.media_format_header'))
						)
						->add($oMediaFromatsBlockRow1 = Admin_Form_Entity::factory('Div')->class('row'));

					$oMediaFromatsBlockRow1->add(Admin_Form_Entity::factory('Div')
						->id($windowId . '-media-formats')
						->class('col-xs-12 margin-top-10')
						->add(
							$this->_object->id
								? $this->_addMediaFormats()
								: Admin_Form_Entity::factory('Code')->html(
									Core_Message::get(Core::_('Media.enable_after_save'), 'warning')
								)
						));
				}

				$oMainTab
					->move($this->getField('name')->divAttr(array('class' => 'form-group col-xs-12')), $oMainRow1)
					->move($this->getField('description')->divAttr(array('class' => 'form-group col-xs-12')), $oMainRow2);

				// Удаляем группу
				$oAdditionalTab->delete($this->getField('media_group_id'));

				$oGroupSelect = Admin_Form_Entity::factory('Select')
					->caption(Core::_('Media_Group.parent_id'))
					->options(array(' … ') + self::fillMediaGroup(0, array($this->_object->id)))
					->name('media_group_id')
					->value($this->_object->media_group_id)
					->divAttr(array('class' => 'form-group col-xs-12 col-md-4'));

				$oMainRow3->add($oGroupSelect);

				$oMainTab
					->move($this->getField('caption')->divAttr(array('class' => 'form-group col-xs-12 col-sm-4')), $oMainRow3)
					->move($this->getField('alt')->divAttr(array('class' => 'form-group col-xs-12 col-sm-4')), $oMainRow3)
					->move($this->getField('datetime')->divAttr(array('class' => 'form-group col-lg-3 col-sm-6 col-xs-12')), $oMainRow4);

				$sFormPath = $this->_Admin_Form_Controller->getPath();

				$filePath = $this->_object->file != '' && Core_File::isFile($this->_object->getFilePath())
					? $this->_object->getFileHref()
					: '';

				$oMainRow5->add(Admin_Form_Entity::factory('File')
					->type("file")
					->caption(Core::_('Media_Item.file'))
					//->divAttr(array('class' => ''))
					->name("file")
					->id("file")
					->largeImage(
						array(
							'path' => $filePath,
							'show_params' => FALSE,
							'delete_onclick' => "$.adminLoad({path: '{$sFormPath}', additionalParams: 'hostcms[checked][{$this->_datasetId}][{$this->_object->id}]=1', action: 'deleteFile', windowId: '{$windowId}'}); return false",
						)
					)
					->smallImage(
						array(
							'show' => FALSE
						)
					)
					->crop(TRUE));
			break;

			case 'media_group':
				if (!$object->id)
				{
					$object->parent_id = Core_Array::getGet('media_group_id');
				}

				parent::setObject($object);

				$title = $this->_object->id
					? Core::_("Media_Group.edit_form_title", $this->_object->name, FALSE)
					: Core::_("Media_Group.add_form_title");

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
					->caption(Core::_('Media_Group.parent_id'))
					->options(array(' … ') + self::fillMediaGroup(0, array($this->_object->id)))
					->name('parent_id')
					->value($this->_object->parent_id)
					->divAttr(array('class' => 'form-group col-xs-12 col-md-4'));

				$oMainRow3->add($oGroupSelect);
			break;
		}

		$this->title($title);

		return $this;
	}

	/*
	 * Add media formats
	 * @return Admin_Form_Entity
	 */
	protected function _addMediaFormats()
	{
		$modalWindowId = preg_replace('/[^A-Za-z0-9_-]/', '', Core_Array::getGet('modalWindowId', '', 'str'));
		$windowId = $modalWindowId ? $modalWindowId : $this->_Admin_Form_Controller->getWindowId();

		return Admin_Form_Entity::factory('Script')
			->value("$(function (){
				mainFormLocker.unlock();
				$.adminLoad({ path: hostcmsBackend + '/media/item/format/index.php', additionalParams: 'media_item_id=" . $this->_object->id . "&parentWindowId=" . $windowId . "', windowId: '{$windowId}-media-formats', loadingScreen: false });
			});");
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @hostcms-event Media_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 * @return self
	 */
	protected function _applyObjectProperty()
	{
		$bAdd = is_null($this->_object->id);

		parent::_applyObjectProperty();

		// для additionaParams используется $_GET
		if (isset($_GET['tmpMediaSource']))
		{
			$_GET['parentWindowId'] = $_GET['tmpMediaSource'];
			unset($_GET['tmpMediaSource']);
			$this->_Admin_Form_Controller->addAdditionalParam('parentWindowId', $_GET['parentWindowId']);
		}

		$modelName = $this->_object->getModelName();

		switch ($modelName)
		{
			case 'media_item':
				/*if (isset($this->_Admin_Form_Controller->request['tmpMediaSource']))
				{
					$this->_Admin_Form_Controller->request['parentWindowId'] = $this->_Admin_Form_Controller->request['tmpMediaSource'];
					unset($this->_Admin_Form_Controller->request['tmpMediaSource']);
				}*/

				$type = Media_Controller::getType($this->_object);

				if ($bAdd && $type == 1)
				{
					ob_start();

					$this->_addMediaFormats()->execute();

					$this->_Admin_Form_Controller->addMessage(ob_get_clean());
				}

				$bForceUpdate = FALSE;

				if (
					// Поле файла существует
					!is_null($aFileData = Core_Array::getFiles('file', NULL))
					// и передан файл
					&& intval($aFileData['size']) > 0)
				{
					if (Core_File::isValidExtension($aFileData['name'], Core::$mainConfig['availableExtension']))
					{
						$this->_object->saveOriginalFile($aFileData['name'], $aFileData['tmp_name']);
						$bForceUpdate = TRUE;
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

				$type = Media_Controller::getType($this->_object);

				$this->_object->type = $type;
				$this->_object->save();

				if ($this->_object->file != '')
				{
					if ($type == 1)
					{
						$aMedia_Formats = Core_Entity::factory('Media_Format')->getAllBySite_id(CURRENT_SITE);
						foreach ($aMedia_Formats as $oMedia_Format)
						{
							$oMedia_Format->saveImage($this->_object, $bForceUpdate);
						}
					}
				}
			break;
		}

		Core_Event::notify(get_class($this) . '.onAfterRedeclaredApplyObjectProperty', $this, array($this->_Admin_Form_Controller));

		return $this;
	}

	/**
	 * Redirect groups tree
	 * @var array
	 */
	static protected $_aGroupTree = array();

	/**
	 * Build visual representation of group tree
	 * @param int $iParentId parent ID
	 * @param array $aExclude exclude group ID
	 * @param int $iLevel current nesting level
	 * @return array
	 */
	static public function fillMediaGroup($iParentId = 0, $aExclude = array(), $iLevel = 0)
	{
		$iParentId = intval($iParentId);
		$iLevel = intval($iLevel);

		if ($iLevel == 0)
		{
			$aTmp = Core_QueryBuilder::select('id', 'parent_id', 'name')
				->from('media_groups')
				->where('deleted', '=', 0)
				->orderBy('name')
				->execute()->asAssoc()->result();

			foreach ($aTmp as $aGroup)
			{
				self::$_aGroupTree[$aGroup['parent_id']][] = $aGroup;
			}
		}

		$aReturn = array();

		if (isset(self::$_aGroupTree[$iParentId]))
		{
			$countExclude = count($aExclude);
			foreach (self::$_aGroupTree[$iParentId] as $childrenGroup)
			{
				if ($countExclude == 0 || !in_array($childrenGroup['id'], $aExclude))
				{
					$aReturn[$childrenGroup['id']] = str_repeat('  ', $iLevel) . $childrenGroup['name'] . ' [' . $childrenGroup['id'] . ']' ;
					$aReturn += self::fillMediaGroup($childrenGroup['id'], $aExclude, $iLevel + 1);
				}
			}
		}

		$iLevel == 0 && self::$_aGroupTree = array();

		return $aReturn;
	}
}
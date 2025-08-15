<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * List_Item Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage List
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class List_Item_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Set object
	 * @param object $object object
	 * @return self
	 */
	public function setObject($object)
	{
		$this
			->addSkipColumn('ico')
			->addSkipColumn('image_large')
			->addSkipColumn('image_small')
			->addSkipColumn('image_large_width')
			->addSkipColumn('image_large_height')
			->addSkipColumn('image_small_width')
			->addSkipColumn('image_small_height');

		if (!$object->id)
		{
			$object->list_id = intval(Core_Array::getGet('list_id'));
			$object->parent_id = intval(Core_Array::getGet('parent_id', 0));
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

		$windowId = $this->_Admin_Form_Controller->getWindowId();

		$oList = $this->_object->List;

		$oMainTab
			->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow4 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow5 = Admin_Form_Entity::factory('Div')->class('row'));

		if (!$this->_object->id)
		{
			// Удаляем стандартный <input>
			$oMainTab->delete($this->getField('value'));

			$oTextarea_ListItemName = Admin_Form_Entity::factory('Textarea')
				->cols(140)
				->rows(5)
				->caption(Core::_('List_Item.add_list_item_name'))
				->divAttr(array('class' => 'form-group col-xs-12'))
				->name('value');

			$oMainRow1->add($oTextarea_ListItemName);
		}
		else
		{
			$oMainTab
				->move($this->getField('value')->divAttr(array('class' => 'form-group col-xs-12')), $oMainRow1);
		}

		$oMainTab->move($this->getField('path')->divAttr(array('class' => 'form-group col-xs-12 col-md-6')), $oMainRow3);

		// Удаляем стандартный <input>
		$oAdditionalTab->delete($this->getField('parent_id'));

		$oList_Items = Core_Entity::factory('List_Item');
		$oList_Items->queryBuilder()
			->where('list_id', '=', $this->_object->list_id);

		$iCountListItems = $oList_Items->getCount();

		if ($iCountListItems < Core::$mainConfig['switchSelectToAutocomplete'])
		{
			// Селектор с родительским макетом
			$oSelect_ListItems = Admin_Form_Entity::factory('Select');
			$oSelect_ListItems
				->options(
					array(' … ') + $this->fillListItemParent(0, $this->_object->id)
				)
				->name('parent_id')
				->value($this->_object->parent_id)
				->caption(Core::_('List_Item.parent_id'))
				->divAttr(array('class' => 'form-group col-xs-12 col-md-6'));

			$oMainRow3->add($oSelect_ListItems);
		}
		else
		{
			$oListItemInput = Admin_Form_Entity::factory('Input')
				->caption(Core::_('List_Item.parent_id'))
				->divAttr(array('class' => 'form-group col-xs-12 col-md-6'))
				->name('parent_name')
				->placeholder(Core::_('Admin.autocomplete_placeholder'));

			if ($this->_object->parent_id)
			{
				$oList_Item = Core_Entity::factory('List_Item', $this->_object->parent_id);
				$oListItemInput->value($oList_Item->value . ' [' . $oList_Item->id . ']');
			}

			$oListItemInputHidden = Admin_Form_Entity::factory('Input')
				->divAttr(array('class' => 'form-group col-xs-12 hidden'))
				->name('parent_id')
				->value($this->_object->parent_id)
				->type('hidden');

			$oCore_Html_Entity_Script_ListItem = Core_Html_Entity::factory('Script')
			->value("
				$('#{$windowId} [name = parent_name]').autocomplete({
					source: function(request, response) {
						$.ajax({
							url: hostcmsBackend + '/list/item/index.php?autocomplete=1&show_parents=1&list_id={$this->_object->list_id}',
							dataType: 'json',
							data: {
								queryString: request.term
							},
							success: function(data) {
								response(data);
							}
						});
					},
					minLength: 1,
					create: function() {
						$(this).data('ui-autocomplete')._renderItem = function(ul, item) {
							return $('<li class=\"autocomplete-suggestion\"></li>')
								.data('item.autocomplete', item)
								.append($('<div class=\"name\">').html($.escapeHtml(item.label)))
								.append($('<div class=\"id\">').html('[' + $.escapeHtml(item.id) + ']'))
								.appendTo(ul);
						}
						$(this).prev('.ui-helper-hidden-accessible').remove();
					},
					select: function(event, ui) {
						$('#{$windowId} [name = parent_id]').val(ui.item.id);
					},
					open: function() {
						$(this).removeClass('ui-corner-all').addClass('ui-corner-top');
					},
					close: function() {
						$(this).removeClass('ui-corner-top').addClass('ui-corner-all');
					}
				});
			");

			$oMainRow3
				->add($oListItemInput)
				->add($oListItemInputHidden)
				->add($oCore_Html_Entity_Script_ListItem);
		}

		$sColorValue = ($this->_object->id && $this->getField('color')->value)
			? $this->getField('color')->value
			: '';

		$this->getField('color')
			->colorpicker(TRUE)
			->value($sColorValue);

		$oLargeFilePath = $this->_object->image_large != '' && Core_File::isFile($this->_object->getLargeFilePath())
			? $this->_object->getLargeFileHref()
			: '';

		$oSmallFilePath = $this->_object->image_small != '' && Core_File::isFile($this->_object->getSmallFilePath())
			? $this->_object->getSmallFileHref()
			: '';

		$sFormPath = $this->_Admin_Form_Controller->getPath();

		$oImageField = Admin_Form_Entity::factory('File')
			//->divAttr(array('class' => ''))
			->name("image")
			->id("image")
			->largeImage(array(
				'max_width' => $oList->image_large_max_width,
				'max_height' => $oList->image_large_max_height,
				'path' => $oLargeFilePath,
				'show_params' => TRUE,
				'place_watermark_checkbox' => FALSE,
				'place_watermark_x_show' => FALSE,
				'place_watermark_y_show' => FALSE,
				'delete_onclick' =>
					"$.adminLoad({path: '{$sFormPath}', additionalParams:
					'hostcms[checked][{$this->_datasetId}][{$this->_object->id}]=1', action: 'deleteLargeImage', windowId: '{$windowId}'}); return false", 'caption' => Core::_('List_Item.image_large'), 'preserve_aspect_ratio_checkbox_checked' => $oList->preserve_aspect_ratio
				)
			)
			->smallImage(array(
				'max_width' => $oList->image_small_max_width,
				'max_height' => $oList->image_small_max_height,
				'path' => $oSmallFilePath,
				'place_watermark_checkbox' => FALSE,
				'create_small_image_from_large_checked' => $oList->create_small_image && $this->_object->image_small == '',
				'delete_onclick' =>
					"$.adminLoad({path: '{$sFormPath}', additionalParams: 'hostcms[checked][{$this->_datasetId}][{$this->_object->id}]=1', action: 'deleteSmallImage', windowId: '{$windowId}'}); return false", 'caption' => Core::_('List_Item.image_small'), 'show_params' => TRUE, 'preserve_aspect_ratio_checkbox_checked' => $oList->preserve_aspect_ratio_small
				)
			)
			->crop(FALSE);

		$oMainRow4->add($oImageField);

		$oMainTab
			->move($this->getField('description')->divAttr(array('class' => 'form-group col-xs-12')), $oMainRow2)
			->move($this->getField('color')->set('data-control', 'hue')->divAttr(array('class' => 'form-group col-xs-12 col-sm-3')), $oMainRow5)
			->move($this->getField('icon')->divAttr(array('class' => 'form-group col-xs-12 col-md-3')), $oMainRow5)
			->move($this->getField('sorting')->divAttr(array('class' => 'form-group col-xs-12 col-md-3')), $oMainRow5)
			->move($this->getField('active')->divAttr(array('class' => 'form-group col-xs-12 col-md-3 margin-top-21')), $oMainRow5);

		$this->title($this->_object->id
			? Core::_('List_Item.edit_title', $oList->name, FALSE)
			: Core::_('List_Item.add_title', $oList->name, FALSE)
		);

		return $this;
	}

	/**
	 * Create visual tree of the directories
	 * @param int $iListItemParentId parent list item ID
	 * @param boolean $bExclude exclude list item ID
	 * @param int $iLevel current nesting level
	 * @return array
	 */
	public function fillListItemParent($iListItemParentId = 0, $bExclude = FALSE, $iLevel = 0)
	{
		$iListItemParentId = intval($iListItemParentId);
		$iLevel = intval($iLevel);

		$oList_Item_Parent = Core_Entity::factory('List_Item', $iListItemParentId);

		$aReturn = array();

		// Дочерние элементы
		$childrenListItems = $oList_Item_Parent->List_Items;
		$childrenListItems->queryBuilder()
			->where('list_id', '=', Core_Array::getGet('list_id', 0));

		$childrenListItems = $childrenListItems->findAll();

		if (count($childrenListItems))
		{
			foreach ($childrenListItems as $childrenListItem)
			{
				if ($bExclude != $childrenListItem->id)
				{
					$aReturn[$childrenListItem->id] = str_repeat('  ', $iLevel) . $childrenListItem->value;
					$aReturn += $this->fillListItemParent($childrenListItem->id, $bExclude, $iLevel + 1);
				}
			}
		}

		return $aReturn;
	}

	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return self
	 */
	public function execute($operation = NULL)
	{
		if (!is_null($operation) && $operation != '')
		{
			$sValue = trim(Core_Array::getPost('value'));

			// Массив значений списка
			$aList_Items = explode("\n", $sValue);
			$sValue = trim(array_shift($aList_Items));

			$list_id = Core_Array::getPost('list_id');
			$id = Core_Array::getPost('id');
			$parent_id = Core_Array::getPost('parent_id');

			$oSameList_Items = Core_Entity::factory('List', $list_id)->List_Items;
			$oSameList_Items
				->queryBuilder()
				->where('list_items.parent_id', '=', $parent_id);

			$oSameList_Item = $oSameList_Items->getByValue($sValue);

			if (!is_null($oSameList_Item) && $oSameList_Item->id != $id)
			{
				$this->addMessage(
					Core_Message::get(Core::_('List_Item.add_lists_items_error'), 'error')
				);
				return TRUE;
			}
		}

		return parent::execute($operation);
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @return self
	 * @hostcms-event List_Item_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 */
	protected function _applyObjectProperty()
	{
		$id = $this->_object->id;

		$description = Core_Array::get($this->_formValues, 'description', '', 'trim');
		$icon = Core_Array::get($this->_formValues, 'icon', '', 'trim');
		$path = Core_Array::get($this->_formValues, 'path', '', 'trim');

		// Apache %2F (/) is forbidden
		strpos($path, '/') !== FALSE
			&& $this->_formValues['path'] = trim(str_replace('/', ' ', $path));

		if (!$id)
		{
			$sValue = Core_Array::get($this->_formValues, 'value', '', 'trim');

			// Массив значений списка
			$aList_Items = explode("\n", $sValue);

			$firstItem = trim(array_shift($aList_Items));

			$aRow = explode(';', $firstItem);

			// Sets value for first list_item
			if (isset($aRow[0]) && strlen($aRow[0]))
			{
				$this->_formValues['value'] = strval($aRow[0]);
				$this->_formValues['description'] = isset($aRow[1]) ? strval($aRow[1]) : $description;
				$this->_formValues['icon'] = isset($aRow[2]) ? strval($aRow[2]) : $icon;
				$this->_formValues['path'] = isset($aRow[3]) ? strval($aRow[3]) : $path;
			}
		}

		parent::_applyObjectProperty();

		$oList = $this->_object->List;

		if (!$id)
		{
			foreach ($aList_Items as $sValue)
			{
				$sValue = trim($sValue);

				$aRow = explode(';', $sValue);

				if (isset($aRow[0]) && strlen($aRow[0]))
				{
					$oSameList_Items = $this->_object->List->List_Items;
					$oSameList_Items
						->queryBuilder()
						->where('list_items.parent_id', '=', $this->_object->parent_id);

					$oSameList_Item = $oSameList_Items->getByValue($aRow[0], FALSE);

					if (is_null($oSameList_Item))
					{
						$oNewListItem = clone $this->_object;
						$oNewListItem->value = strval($aRow[0]);
						$oNewListItem->description = isset($aRow[1]) && strlen($aRow[1]) ? strval($aRow[1]) : $description;
						$oNewListItem->icon = isset($aRow[2]) && strlen($aRow[2]) ? strval($aRow[2]) : $icon;
						$oNewListItem->path = '';
						$oNewListItem->save();
					}
				}
			}
		}

		// Обработка картинок
		$param = array();

		$large_image = $small_image = '';

		$create_small_image_from_large = Core_Array::getPost('create_small_image_from_large_small_image');

		$bLargeImageIsCorrect =
			// Поле файла большого изображения существует
			!is_null($aFileData = Core_Array::getFiles('image', NULL))
			// и передан файл
			&& intval($aFileData['size']) > 0;

		if ($bLargeImageIsCorrect)
		{
			// Проверка на допустимый тип файла
			if (Core_File::isValidExtension($aFileData['name'], Core::$mainConfig['availableExtension']))
			{
				// Удаление файла большого изображения
				if ($this->_object->image_large)
				{
					$this->_object->deleteLargeImage();
				}

				$file_name = $aFileData['name'];

				// Не преобразовываем название загружаемого файла
				if (!$oList->change_filename)
				{
					$large_image = $file_name;
				}
				else
				{
					// Определяем расширение файла
					$ext = Core_File::getExtension($aFileData['name']);

					$large_image = Core_Guid::get() . '.' .$ext;
				}
			}
			else
			{
				$this->addMessage(Core_Message::get(Core::_('Core.extension_does_not_allow', Core_File::getExtension($aFileData['name'])), 'error'));
			}
		}

		$aSmallFileData = Core_Array::getFiles('small_image', NULL);
		$bSmallImageIsCorrect =
			// Поле файла малого изображения существует
			!is_null($aSmallFileData)
			&& $aSmallFileData['size'];

		// Задано малое изображение и при этом не задано создание малого изображения
		// из большого или задано создание малого изображения из большого и
		// при этом не задано большое изображение.

		if ($bSmallImageIsCorrect || $create_small_image_from_large && $bLargeImageIsCorrect)
		{
			// Удаление файла малого изображения
			if ($this->_object->image_small)
			{
				// !! дописать метод
				$this->_object->deleteSmallImage();
			}

			// Явно указано малое изображение
			if ($bSmallImageIsCorrect
				&& Core_File::isValidExtension($aSmallFileData['name'],
				Core::$mainConfig['availableExtension']))
			{
				// Для инфогруппы ранее задано изображение
				if ($this->_object->image_large != '')
				{
					// Существует ли большое изображение
					$create_large_image = FALSE;
				}
				else // Для информационной группы ранее не задано большое изображение
				{
					$create_large_image = empty($large_image);
				}

				$file_name = $aSmallFileData['name'];

				// Не преобразовываем название загружаемого файла
				if (!$oList->change_filename)
				{
					if ($create_large_image)
					{
						$large_image = $file_name;
						$small_image = 'small_' . $large_image;
					}
					else
					{
						$small_image = $file_name;
					}
				}
				else
				{
					// Определяем расширение файла
					$ext = Core_File::getExtension($file_name);

					$small_image = Core_Guid::get() . '.' . $ext;

				}
			}
			elseif ($create_small_image_from_large && $bLargeImageIsCorrect)
			{
				$small_image = 'small_' . $large_image;
			}
			// Тип загружаемого файла является недопустимым для загрузки файла
			else
			{
				$this->addMessage(Core_Message::get(Core::_('Core.extension_does_not_allow', Core_File::getExtension($aSmallFileData['name'])), 'error'));
			}
		}

		if ($bLargeImageIsCorrect || $bSmallImageIsCorrect)
		{
			if ($bLargeImageIsCorrect)
			{
				// Путь к файлу-источнику большого изображения;
				$param['large_image_source'] = $aFileData['tmp_name'];
				// Оригинальное имя файла большого изображения
				$param['large_image_name'] = $aFileData['name'];
			}

			if ($bSmallImageIsCorrect)
			{
				// Путь к файлу-источнику малого изображения;
				$param['small_image_source'] = $aSmallFileData['tmp_name'];
				// Оригинальное имя файла малого изображения
				$param['small_image_name'] = $aSmallFileData['name'];
			}

			// Путь к создаваемому файлу большого изображения;
			$param['large_image_target'] = !empty($large_image)
				? $this->_object->getPath() . $large_image
				: '';

			// Путь к создаваемому файлу малого изображения;
			$param['small_image_target'] = !empty($small_image)
				? $this->_object->getPath() . $small_image
				: '' ;

			// Использовать большое изображение для создания малого
			$param['create_small_image_from_large'] = !is_null(Core_Array::getPost('create_small_image_from_large_small_image'));

			// Значение максимальной ширины большого изображения
			$param['large_image_max_width'] = Core_Array::getPost('large_max_width_image', 0);

			// Значение максимальной высоты большого изображения
			$param['large_image_max_height'] = Core_Array::getPost('large_max_height_image', 0);

			// Значение максимальной ширины малого изображения;
			$param['small_image_max_width'] = Core_Array::getPost('small_max_width_small_image');

			// Значение максимальной высоты малого изображения;
			$param['small_image_max_height'] = Core_Array::getPost('small_max_height_small_image');

			// Сохранять пропорции изображения для большого изображения
			$param['large_image_preserve_aspect_ratio'] = !is_null(Core_Array::getPost('large_preserve_aspect_ratio_image'));

			// Сохранять пропорции изображения для малого изображения
			$param['small_image_preserve_aspect_ratio'] = !is_null(Core_Array::getPost('small_preserve_aspect_ratio_small_image'));

			$this->_object->createDir();

			$result = Core_File::adminUpload($param);

			if ($result['large_image'])
			{
				$this->_object->image_large = $large_image;
				$this->_object->setLargeImageSizes();
			}

			if ($result['small_image'])
			{
				$this->_object->image_small = $small_image;
				$this->_object->setSmallImageSizes();
			}
		}

		$this->_object->save();

		Core_Event::notify(get_class($this) . '.onAfterRedeclaredApplyObjectProperty', $this, array($this->_Admin_Form_Controller));

		return $this;
	}
}
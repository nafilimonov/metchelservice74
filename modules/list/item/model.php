<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * List_Item_Model
 *
 * @package HostCMS
 * @subpackage List
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class List_Item_Model extends Core_Entity
{
	/**
	 * Column consist item's name
	 * @var string
	 */
	protected $_nameColumn = 'value';

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'list' => array(),
		'list_item' => array('foreign_key' => 'parent_id'),
		'user' => array()
	);

	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'list_item' => array('foreign_key' => 'parent_id')
	);

	/**
	 * Default sorting for models
	 * @var array
	 */
	protected $_sorting = array(
		'list_items.sorting' => 'ASC',
		'list_items.value' => 'ASC'
	);

	/**
	 * List of preloaded values
	 * @var array
	 */
	protected $_preloadValues = array(
		'sorting' => 0,
		'active' => 1,
		'image_small_height' => 0,
		'image_small_width' => 0,
		'image_large_height' => 0,
		'image_large_width' => 0,
	);

	/**
	 * Constructor.
	 * @param int $id entity ID
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		if (is_null($id) && !$this->loaded())
		{
			$oUser = Core_Auth::getCurrentUser();
			$this->_preloadValues['user_id'] = is_null($oUser) ? 0 : $oUser->id;
		}
	}

	/**
	 * Get path include CMS_FOLDER
	 * @return string
	 */
	public function getPath()
	{
		return CMS_FOLDER . $this->List->Site->uploaddir . 'lists/list_' . $this->List->id . '/' . Core_File::getNestingDirPath($this->id, $this->List->Site->nesting_level) . '/item_' . $this->id . '/';
	}

	/**
	 * Get href
	 * @return string
	 */
	public function getHref()
	{
		return '/' . $this->List->Site->uploaddir . 'lists/list_' . $this->List->id . '/' . Core_File::getNestingDirPath($this->id, $this->List->Site->nesting_level) . '/item_' . $this->id . '/';
	}

	/**
	 * Get small image path
	 * @return string
	 */
	public function getSmallFilePath()
	{
		return $this->getPath() . $this->image_small;
	}

	/**
	 * Get small image href
	 * @return string
	 */
	public function getSmallFileHref()
	{
		return $this->getHref() . rawurlencode($this->image_small);
	}

	/**
	 * Get large image path
	 * @return string
	 */
	public function getLargeFilePath()
	{
		return $this->getPath() . $this->image_large;
	}

	/**
	 * Get large image href
	 * @return string
	 */
	public function getLargeFileHref()
	{
		return $this->getHref() . rawurlencode($this->image_large);
	}

	/**
	 * Create directory for item
	 * @return self
	 */
	public function createDir()
	{
		clearstatcache();

		if (!Core_File::isDir($this->getPath()))
		{
			try
			{
				Core_File::mkdir($this->getPath(), CHMOD, TRUE);
			} catch (Exception $e) {}
		}

		return $this;
	}

	/**
	 * Set large image sizes
	 * @return self
	 */
	public function setLargeImageSizes()
	{
		$path = $this->getLargeFilePath();

		if (Core_File::isFile($path))
		{
			$aSizes = Core_Image::instance()->getImageSize($path);

			if ($aSizes)
			{
				$this->image_large_width = $aSizes['width'];
				$this->image_large_height = $aSizes['height'];
				$this->save();
			}
		}

		return $this;
	}

	/**
	 * Set small image sizes
	 * @return self
	 */
	public function setSmallImageSizes()
	{
		$path = $this->getSmallFilePath();

		if (Core_File::isFile($path))
		{
			$aSizes = Core_Image::instance()->getImageSize($path);

			if ($aSizes)
			{
				$this->image_small_width = $aSizes['width'];
				$this->image_small_height = $aSizes['height'];
				$this->save();
			}
		}

		return $this;
	}

	/**
	 * Delete item's large image
	 * @return self
	 * @hostcms-event list_item.onAfterDeleteLargeImage
	 */
	public function deleteLargeImage()
	{
		$fileName = $this->getLargeFilePath();
		if ($this->image_large != '' && Core_File::isFile($fileName))
		{
			try
			{
				Core_File::delete($fileName);
			} catch (Exception $e) {}

			Core_Event::notify($this->_modelName . '.onAfterDeleteLargeImage', $this);

			$this->image_large = '';
			$this->save();
		}
		return $this;
	}

	/**
	 * Delete item's small image
	 * @return self
	 * @hostcms-event list_item.onAfterDeleteSmallImage
	 */
	public function deleteSmallImage()
	{
		$fileName = $this->getSmallFilePath();
		if ($this->image_small != '' && Core_File::isFile($fileName))
		{
			try
			{
				Core_File::delete($fileName);
			} catch (Exception $e) {}

			Core_Event::notify($this->_modelName . '.onAfterDeleteSmallImage', $this);

			$this->image_small = '';
			$this->save();
		}
		return $this;
	}

	/**
	 * Delete item directory
	 * @return self
	 */
	public function deleteDir()
	{
		// Удаляем файл большого изображения элемента
		$this->deleteLargeImage();

		// Удаляем файл малого изображения элемента
		$this->deleteSmallImage();

		if (Core_File::isDir($this->getPath()))
		{
			try
			{
				Core_File::deleteDir($this->getPath());
			} catch (Exception $e) {}
		}

		return $this;
	}

	/**
	 * Move field to another list
	 * @param int $list_id target list id
	 * @return Core_Entity
	 * @hostcms-event list_item.onBeforeMove
	 * @hostcms-event list_item.onAfterMove
	 */
	public function move($list_id)
	{
		Core_Event::notify($this->_modelName . '.onBeforeMove', $this, array($list_id));

		$this->list_id = $list_id;
		$this->save();

		$this->_moveListItems($this, $list_id);

		Core_Event::notify($this->_modelName . '.onAfterMove', $this);

		return $this;
	}

	/**
	 * Move children items
	 * @param List_Item_Model $oList_Item
	 * @param int $list_id
	 * @return self
	 */
	protected function _moveListItems(List_Item_Model $oList_Item, $list_id)
	{
		$aList_Items = $oList_Item->List_Items->findAll(FALSE);

		foreach ($aList_Items as $oList_Item_Children)
		{
			$oList_Item_Children->list_id = $list_id;
			$oList_Item_Children->save();

			$this->_moveListItems($oList_Item_Children, $list_id);
		}

		return $this;
	}

	/**
	 * Change active mode
	 * @return self
	 */
	public function changeStatus()
	{
		$this->active = 1 - $this->active;
		return $this->save();
	}

	/**
	 * Backend badge
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function valueBadge($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		if (strlen($this->color))
		{
			?><span class="list-item-color-badge" style="background-color: <?php echo htmlspecialchars($this->color)?>; box-shadow: 0 0 0 1px <?php echo Core_Str::hex2darker($this->color, 0.1)?>"></span><?php
		}

		$count = $this->List_Items->getCount();
		$count && Core_Html_Entity::factory('Span')
			->class('badge badge-hostcms badge-square margin-left-5')
			->value($count)
			->execute();
	}

	/**
	 * Get parent list item
	 * @return List_Item_Model|NULL
	 */
	public function getParent()
	{
		return $this->parent_id
			? Core_Entity::factory('List_Item', $this->parent_id)
			: NULL;
	}

	/**
	 * Merge list item with another one
	 * @param List_Item_Model $oList_Item list item object
	 * @return self
	 */
	public function merge(List_Item_Model $oList_Item)
	{
		$this->sorting == 0
			&& $oList_Item->sorting > 0
			&& $this->sorting = $oList_Item->sorting;

		$this->description == ''
			&& $oList_Item->description != ''
			&& $this->description = $oList_Item->description;

		$this->icon == ''
			&& $oList_Item->icon != ''
			&& $this->icon = $oList_Item->icon;

		$this->save();

		$oProperties = Core_Entity::factory('Property');
		$oProperties->queryBuilder()
			->where('type', '=', 3)
			->where('list_id', '=', $oList_Item->list_id);

		$aProperties = $oProperties->findAll(FALSE);
		foreach ($aProperties as $oProperty)
		{
			Core_QueryBuilder::update('property_value_ints')
				->columns(array('value' => $this->id))
				->where('property_id', '=', $oProperty->id)
				->where('value', '=', $oList_Item->id)
				->execute();
		}

		$oList_Item->markDeleted();

		return $this;
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function imgBackend()
	{
		if (strlen($this->image_small) || strlen($this->image_large))
		{
			$path = strlen($this->image_small)
				? $this->getSmallFileHref()
				: $this->getLargeFileHref();

			$ext = Core_File::getExtension($path);

			if (in_array(mb_strtoupper($ext), Core_File::getResizeExtensions()))
			{
				$srcImg = htmlspecialchars($path);
				$dataContent = '<img class="backend-preview" src="' . $srcImg . '" />';

				return '<img data-toggle="popover" data-trigger="hover" data-html="true" data-placement="top" data-content="' . htmlspecialchars($dataContent) . '" class="backend-thumbnail" src="' . $srcImg . '" />';
			}
		}
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return self
	 * @hostcms-event list_item.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}
		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		$this->List_Items->deleteAll(FALSE);

		$this->deleteDir();

		return parent::delete($primaryKey);
	}

	/**
	 * Make url path
	 * @return self
	 * @hostcms-event list_item.onAfterMakePath
	 */
	public function makePath()
	{
		switch ($this->List->url_type)
		{
			case 0:
			default:
				// nothing to do
			break;
			case 1:
				try {
					Core::$mainConfig['translate'] && $sTranslated = Core_Str::translate($this->value);

					$this->path = Core::$mainConfig['translate'] && strlen((string) $sTranslated)
						? $sTranslated
						: $this->value;

					$this->path = Core_Str::transliteration($this->path);

				} catch (Exception $e) {
					$this->path = Core_Str::transliteration($this->value);
				}
			break;
			case 2:
				if ($this->id)
				{
					$this->path = $this->id;
				}
			break;
		}

		Core_Event::notify($this->_modelName . '.onAfterMakePath', $this);

		return $this;
	}

	/**
	 * Get XML for entity and children entities
	 * @return string
	 * @hostcms-event list_item.onBeforeRedeclaredGetXml
	 */
	public function getXml()
	{
		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredGetXml', $this);

		$this->_prepareData();

		return parent::getXml();
	}

	/**
	 * Get stdObject for entity and children entities
	 * @return stdObject
	 * @hostcms-event list_item.onBeforeRedeclaredGetStdObject
	 */
	public function getStdObject($attributePrefix = '_')
	{
		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredGetStdObject', $this);

		$this->_prepareData();

		return parent::getStdObject($attributePrefix);
	}

	/**
	 * Prepare entity and children entities
	 * @return self
	 */
	protected function _prepareData()
	{
		$this->clearXmlTags();

		if ($this->image_small != '' || $this->image_large != '')
		{
			$this->_isTagAvailable('dir')
				&& $this->addXmlTag('dir', $this->getHref());
		}
		else
		{
			$this->addForbiddenTags(array('image_large', 'image_small', 'image_small_height', 'image_small_width', 'image_large_height', 'image_large_width'));
		}

		return $this;
	}

	/**
	 * Check and correct duplicate path
	 * @return self
	 * @hostcms-event list_item.onAfterCheckDuplicatePath
	 */
	public function checkDuplicatePath()
	{
		if (strlen($this->path))
		{
			$oSameListItem = $this->List->List_Items->getByPath($this->path);
			if (!is_null($oSameListItem) && $oSameListItem->id != $this->id)
			{
				$this->path = Core_Guid::get();
			}
		}

		Core_Event::notify($this->_modelName . '.onAfterCheckDuplicatePath', $this);

		return $this;
	}

	/**
	 * Save object.
	 *
	 * @return Core_Entity
	 */
	public function save()
	{
		if (is_null($this->path))
		{
			$this->makePath();
		}
		elseif (in_array('path', $this->_changedColumns))
		{
			$this->checkDuplicatePath();
		}

		parent::save();

		if ($this->path == '' && !$this->deleted && $this->makePath())
		{
			$this->path != '' && $this->save();
		}

		return $this;
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event list_item.onBeforeGetRelatedSite
	 * @hostcms-event list_item.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->List->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}
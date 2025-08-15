<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * List_Model
 *
 * @package HostCMS
 * @subpackage List
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2024 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class List_Model extends Core_Entity
{
	/**
	 * Backend property
	 */
	public $img = 1;

	/**
	 * Backend property
	 */
	public $items = 0;

	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'list_item' => array()
	);

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'list_dir' => array(),
		'user' => array(),
		'site' => array()
	);

	/**
	 * Default sorting for models
	 * @var array
	 */
	protected $_sorting = array(
		'lists.name' => 'ASC',
	);

	/**
	 * List of preloaded values
	 * @var array
	 */
	protected $_preloadValues = array(
		'image_small_max_width' => 100,
		'image_large_max_width' => 800,
		'image_small_max_height' => 100,
		'image_large_max_height' => 800
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
			$this->_preloadValues['site_id'] = defined('CURRENT_SITE') ? CURRENT_SITE : 0;
		}
	}

	/**
	 * Copy object
	 * @return Core_Entity
	 * @hostcms-event list.onAfterRedeclaredCopy
	 */
	public function copy()
	{
		$newObject = parent::copy();

		$aTmp = array();

		$aList_Items = $this->List_Items->findAll(FALSE);
		foreach ($aList_Items as $oList_Item)
		{
			$oNew_List_Item = clone $oList_Item;
			$newObject->add($oNew_List_Item);

			$aTmp[$oList_Item->id] = $oNew_List_Item->id;
		}

		$aNew_List_Items = $newObject->List_Items->findAll(FALSE);
		foreach ($aNew_List_Items as $oList_Item)
		{
			$oList_Item->parent_id = Core_Array::get($aTmp, $oList_Item->parent_id, 0);
			$oList_Item->save();
		}

		Core_Event::notify($this->_modelName . '.onAfterRedeclaredCopy', $newObject, array($this));

		return $newObject;
	}

	/**
	 * Move item to another group
	 * @param int $list_dir_id target group id
	 * @return Core_Entity
	 * @hostcms-event list.onBeforeMove
	 * @hostcms-event list.onAfterMove
	 */
	public function move($list_dir_id)
	{
		Core_Event::notify($this->_modelName . '.onBeforeMove', $this, array($list_dir_id));

		$this->list_dir_id = $list_dir_id;
		$this->save();

		Core_Event::notify($this->_modelName . '.onAfterMove', $this);

		return $this;
	}

	/**
	 * Get list by name and site
	 * @param String $list_name list name
	 * @param int $site_id site id
	 * @return List|NULL
	 */
	public function getByNameAndSite($list_name, $site_id)
	{
		$this->queryBuilder()
			->clear()
			->where('name', '=', $list_name)
			->where('site_id', '=', $site_id)
			->limit(1);

		$aLists = $this->findAll();

		return isset($aLists[0])
			? $aLists[0]
			: NULL;
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return self
	 * @hostcms-event list.onBeforeRedeclaredDelete
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

		return parent::delete($primaryKey);
	}

	/**
	 * Merge Lists
	 * @param List_Model $oObject
	 * @return self
	 */
	public function merge(List_Model $oObject)
	{
		if (Core::moduleIsActive('form'))
		{
			Core_QueryBuilder::update('form_fields')
				->set('list_id', $this->id)
				->where('list_id', '=', $oObject->id)
				->execute();
		}

		Core_QueryBuilder::update('properties')
			->set('list_id', $this->id)
			->where('list_id', '=', $oObject->id)
			->execute();

		Core_QueryBuilder::update('list_items')
			->set('list_id', $this->id)
			->where('list_id', '=', $oObject->id)
			->execute();

		$oObject->markDeleted();

		return $this;
	}

	/**
	 * Backend badge
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function itemsBadge($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		$count = $this->List_Items->getCount();
		$count && Core_Html_Entity::factory('Span')
			->class('badge badge-ico badge-azure white')
			->value($count < 100 ? $count : '∞')
			->title($count)
			->execute();
	}

	/**
	 * List Items Tree
	 * @var mixed
	 */
	protected $_aListItemsTree = NULL;

	/**
	 * Get List Items Tree with spaces
	 * @param int $parent_id Parent ID, default 0
	 * @param int $iLevel Level, default 0
	 */
	public function getListItemsTree($parent_id = 0, $iLevel = 0)
	{
		// Fill ListItemsTree
		if (is_null($this->_aListItemsTree))
		{
			$oList_Items = $this->List_Items;
			$oList_Items->queryBuilder()
				->where('list_items.active', '=', 1);

			$this->_aListItemsTree = array();

			$aList_Items = $oList_Items->findAll(FALSE);
			foreach ($aList_Items as $oList_Item)
			{
				$this->_aListItemsTree[$oList_Item->parent_id][] = $oList_Item;
			}
		}

		$aReturn = array();
		if (isset($this->_aListItemsTree[$parent_id]))
		{
			foreach ($this->_aListItemsTree[$parent_id] as $oList_Item)
			{
				$aAttr = array();

				if (strlen($oList_Item->color))
				{
					$color = $oList_Item->color;

					$colorVal = hexdec(ltrim($color, '#'));

					if (strlen($colorVal) == 6)
					{
						$red = 0xFF & ($colorVal >> 16);
						$green = 0xFF & ($colorVal >> 8);
						$blue = 0xFF & $colorVal;

						if ($red < 224 && $green < 224 && $blue < 224)
						{
							$color = '#000';
						}
					}

					$aAttr['style'] = 'color: ' . $color;
				}

				$aReturn[$oList_Item->id] = array(
					'value' => str_repeat('  ', $iLevel) . $oList_Item->value,
					'attr' => $aAttr
				);

				$aReturn += $this->getListItemsTree($oList_Item->id, $iLevel + 1);
			}
		}

		return $aReturn;
	}

	/**
	 * Backend badge
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function nameBadge($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		if ($this->url_type)
		{
			$color = '#53a93f';

			Core_Html_Entity::factory('Span')
				->class('badge badge-round badge-max-width')
				->style("border-color: " . $color . "; color: " . Core_Str::hex2darker($color, 0.2) . "; background-color: " . Core_Str::hex2lighter($color, 0.88))
				->title(Core::_('List.url_type'))
				->value('URL: ' . Core::_('List.url_type' . $this->url_type))
				->execute();
		}
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event list.onBeforeGetRelatedSite
	 * @hostcms-event list.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}
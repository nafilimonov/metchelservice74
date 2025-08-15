<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * List_Dir_Model
 *
 * @package HostCMS
 * @subpackage List
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class List_Dir_Model extends Core_Entity
{
	/**
	 * Backend property
	 */
	public $img = 0;

	/**
	 * Backend property
	 */
	public $img_list_items = '';

	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'list' => array(),
		'list_dir' => array('foreign_key' => 'parent_id')
	);

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'list_dir' => array('foreign_key' => 'parent_id'),
		'site' => array(),
		'user' => array()
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
	 * Get parent comment
	 * @return List_Dir_Model|NULL
	 */
	public function getParent()
	{
		return $this->parent_id
			? Core_Entity::factory('List_Dir', $this->parent_id)
			: NULL;
	}

	public function changeSite($siteId)
	{
		$aList_Dirs = $this->List_Dirs->findAll(FALSE);

		foreach($aList_Dirs as $oList_Dir)
		{
			$oList_Dir->changeSite($siteId);
		}

		$aLists = $this->Lists->findAll(FALSE);

		foreach($aLists as $oList)
		{
			$oList
				->site_id($siteId)
				->save();
		}

		$this
			->site_id($siteId)
			->save();
	}

	/**
	 * Move group to another
	 * @param int $parent_id group id
	 * @return self
	 * @hostcms-event list_dir.onBeforeMove
	 * @hostcms-event list_dir.onAfterMove
	 */
	public function move($parent_id)
	{
		Core_Event::notify($this->_modelName . '.onBeforeMove', $this, array($parent_id));

		$this->parent_id = $parent_id;
		$this->save();

		Core_Event::notify($this->_modelName . '.onAfterMove', $this);

		return $this;
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return self
	 * @hostcms-event list_dir.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}
		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		$this->List_Dirs->deleteAll(FALSE);
		$this->Lists->deleteAll(FALSE);

		return parent::delete($primaryKey);
	}

	public function getListsCount()
	{
		$iListsCount = 0;

		$aList_Dirs = $this->List_Dirs->findAll(FALSE);

		foreach($aList_Dirs as $oList_Dir)
		{
			$iListsCount = $oList_Dir->getListsCount();
		}

		return $iListsCount + $this->Lists->getCount();
	}

	/**
	 * Backend badge
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function nameBadge($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		//$count = $this->Lists->getCount();
		$count = $this->getListsCount();
		$count && Core_Html_Entity::factory('Span')
			->class('badge badge-hostcms badge-square')
			->value($count)
			->execute();
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event list_dir.onBeforeGetRelatedSite
	 * @hostcms-event list_dir.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}
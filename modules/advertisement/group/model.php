<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Advertisement_Group_Model
 *
 * @package HostCMS
 * @subpackage Advertisement
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Advertisement_Group_Model extends Core_Entity
{
	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'advertisement_group_list' => array(),
		'advertisement' => array('through' => 'advertisement_group_list')
	);

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
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
	 * Copy object
	 * @return Core_Entity
	 * @hostcms-event advertisement_group.onAfterRedeclaredCopy
	 */
	public function copy()
	{
		$newObject = parent::copy();

		$aAdvertisementGroupLists = $this->Advertisement_Group_Lists->findAll();
		foreach ($aAdvertisementGroupLists as $oAdvertisementGroupList)
		{
			$oNewoaAdvertisementGroupList = clone $oAdvertisementGroupList;
			$newObject->add($oNewoaAdvertisementGroupList);
		}

		Core_Event::notify($this->_modelName . '.onAfterRedeclaredCopy', $newObject, array($this));

		return $newObject;
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return self
	 * @hostcms-event advertisement_group.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}

		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		$this->Advertisement_Group_Lists->deleteAll(FALSE);

		return parent::delete($primaryKey);
	}

	/**
	 * Backend badge
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function nameBadge($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		$count = $this->Advertisements->getCount();
		$count && Core_Html_Entity::factory('Span')
			->class('badge badge-hostcms badge-square')
			->value($count)
			->execute();
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event advertisement_group.onBeforeGetRelatedSite
	 * @hostcms-event advertisement_group.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}
<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Advertisement_Group_List_Model
 *
 * @package HostCMS
 * @subpackage Advertisement
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2021 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Advertisement_Group_List_Model extends Core_Entity
{
	/**
	 * Disable markDeleted()
	 * @var mixed
	 */
	protected $_marksDeleted = NULL;

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'advertisement_group' => array(),
		'advertisement' => array()
	);

	/**
	 * Column consist item's name
	 * @var string
	 */
	protected $_nameColumn = 'id';

	/**
	 * Get Advertisement_Group_List by advertisement id
	 * @param int $advertisement_id advertisement id
	 * @return Core_Entity|null
	 */
	public function getByAdvertisementId($advertisement_id)
	{
		$this
			->queryBuilder()
			//->clear()
			->where('advertisement_id', '=', $advertisement_id)
			->limit(1);

		$aAdvertisement_Group_List = $this->findAll();

		return isset($aAdvertisement_Group_List[0]) ? $aAdvertisement_Group_List[0] : NULL;
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event advertisement_group_list.onBeforeGetRelatedSite
	 * @hostcms-event advertisement_group_list.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Advertisement->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}
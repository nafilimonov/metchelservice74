<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Advertisement_Show_Model
 *
 * @package HostCMS
 * @subpackage Advertisement
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2021 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Advertisement_Show_Model extends Core_Entity
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
		'advertisement' => array()
	);

	/**
	 * List of preloaded values
	 * @var array
	 */
	protected $_preloadValues = array(
		'click' => 0
	);

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event advertisement_show.onBeforeGetRelatedSite
	 * @hostcms-event advertisement_show.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Advertisement->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}
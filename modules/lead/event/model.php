<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Lead_Event_Model
 *
 * @package HostCMS
 * @subpackage Lead
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2021 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Lead_Event_Model extends Core_Entity
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
		'lead' => array(),
		'event' => array(),
	);

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event lead_event.onBeforeGetRelatedSite
	 * @hostcms-event lead_event.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Lead->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}
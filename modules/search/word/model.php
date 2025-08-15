<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Search_Word_Model
 *
 * @package HostCMS
 * @subpackage Search
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2021 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Search_Word_Model extends Core_Entity
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
		'search_page' => array()
	);

	/**
	 * List of preloaded values
	 * @var array
	 */
	protected $_preloadValues = array(
		'weight' => 0
	);

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event search_word.onBeforeGetRelatedSite
	 * @hostcms-event search_word.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Search_Page->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}
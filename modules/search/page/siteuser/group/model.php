<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Search_Page_Siteuser_Group_Model
 *
 * @package HostCMS
 * @subpackage Search
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2021 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Search_Page_Siteuser_Group_Model extends Core_Entity
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
		'search_page' => array(),
		'siteuser_group' => array()
	);

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event search_page_siteuser_group.onBeforeGetRelatedSite
	 * @hostcms-event search_page_siteuser_group.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Search_Page->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}
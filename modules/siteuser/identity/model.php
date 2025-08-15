<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Identity_Model
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2021 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_Identity_Model extends Core_Entity
{
	/**
	 * Column consist item's name
	 * @var string
	 */
	protected $_nameColumn = 'id';

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
		'siteuser' => array(),
	);

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event siteuser_identity.onBeforeGetRelatedSite
	 * @hostcms-event siteuser_identity.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Siteuser->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}
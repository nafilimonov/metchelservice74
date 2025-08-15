<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Advertisement_Contextualword_Model
 *
 * @package HostCMS
 * @subpackage Advertisement
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2021 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Advertisement_Contextualword_Model extends Core_Entity
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
		'user' => array(),
		'advertisement' => array()
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
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event advertisement_contextualword.onBeforeGetRelatedSite
	 * @hostcms-event advertisement_contextualword.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Advertisement->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}
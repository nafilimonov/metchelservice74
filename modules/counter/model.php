<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Counter_Model
 *
 * @package HostCMS
 * @subpackage Counter
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2021 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Counter_Model extends Core_Entity
{
	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'site' => array(),
	);

	/**
	 * List of preloaded values
	 * @var array
	 */
	protected $_preloadValues = array(
		'hits' => 0,
		'hosts' => 0,
		'sessions' => 0,
		'new_users' => 0,
		'bots' => 0,
		'sent' => 0
	);

	/**
	 * Backend property
	 * @var mixed
	 */
	public $adminSum = NULL;

	/**
	 * Disable markDeleted()
	 * @var mixed
	 */
	protected $_marksDeleted = NULL;

	/**
	 * Constructor.
	 * @param int $id entity ID
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		if (is_null($id) && !$this->loaded())
		{
			$this->_preloadValues['site_id'] = defined('CURRENT_SITE') ? CURRENT_SITE : 0;
			$this->_preloadValues['date'] = Core_Date::timestamp2sql(time());
		}
	}

	/**
	 * Insert new object data into database
	 * @return Core_ORM
	 * @hostcms-event modelname.onBeforeCreate
	 * @hostcms-event modelname.onAfterCreate
	 */
	public function create()
	{
		if (!$this->_saved)
		{
			$this->id = Counter_Controller::getPrimaryKeyByDate($this->date) . $this->site_id;
		}

		return parent::create();
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event counter.onBeforeGetRelatedSite
	 * @hostcms-event counter.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}
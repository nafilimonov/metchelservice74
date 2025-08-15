<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Cdn_Model
 *
 * @package HostCMS
 * @subpackage Cdn
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2020 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Cdn_Model extends Core_Entity
{
	/**
	 * Backend property
	 * @var string
	 */
	public $active = NULL;

	/**
	 * Backend property
	 * @var string
	 */
	public $default = NULL;

	/**
	 * Default sorting for models
	 * @var array
	 */
	protected $_sorting = array(
		'cdns.name' => 'ASC'
	);

	/**
	 * List of preloaded values
	 * @var array
	 */
	protected $_preloadValues = array(
		'balance' => 0,
	);

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'user' => array()
	);

	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'cdn_site' => array()
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
			$this->_preloadValues['balance_datetime'] = Core_Date::timestamp2sql(time());
		}
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return self
	 * @hostcms-event cdn.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}

		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		$this->Cdn_Sites->deleteAll(FALSE);

		return parent::delete($primaryKey);
	}

	/**
	 * Change CDN status
	 * @return self
	 * @hostcms-event cdn.onBeforeChangeActive
	 * @hostcms-event cdn.onAfterChangeActive
	 */
	public function changeActive()
	{
		Core_Event::notify($this->_modelName . '.onBeforeChangeActive', $this);

		$oSite = Core_Entity::factory('Site', CURRENT_SITE);

		$oCdn_Site = $oSite->Cdn_Sites->getByCdn_id($this->id);

		if (is_null($oCdn_Site))
		{
			$oCdn_Site = Core_Entity::factory('Cdn_Site');
			$oCdn_Site->site_id = CURRENT_SITE;
			$oCdn_Site->cdn_id = $this->id;
			$oCdn_Site->active = 0;
		}

		$oCdn_Site->active = 1 - $oCdn_Site->active;
		$oCdn_Site->save();

		Core_Event::notify($this->_modelName . '.onAfterChangeActive', $this);

		return $this;
	}

	/**
	 * Switch default status
	 * @return self
	 */
	public function setDefault()
	{
		$this->save();

		$oSite = Core_Entity::factory('Site', CURRENT_SITE);
		$oCdn_Sites = $oSite->Cdn_Sites;
		$oCdn_Sites->queryBuilder()
			->where('cdn_sites.cdn_id', '!=', $this->id)
			->where('cdn_sites.default', '=', 1);

		$aCdn_Sites = $oCdn_Sites->findAll(FALSE);

		foreach ($aCdn_Sites as $oCdn_Site)
		{
			$oCdn_Site->default = 0;
			$oCdn_Site->save();
		}

		$oCdn_Site = $oSite->Cdn_Sites->getByCdn_id($this->id);

		if (is_null($oCdn_Site))
		{
			$oCdn_Site = Core_Entity::factory('Cdn_Site');
			$oCdn_Site->site_id = CURRENT_SITE;
			$oCdn_Site->cdn_id = $this->id;
		}

		$oCdn_Site->default = 1;
		$oCdn_Site->save();

		return $this;
	}

	/**
	 * Show current balance with measure
	 * @return string
	 */
	public function balance()
	{
		try {
			$Cdn_Controller = Cdn_Controller::instance($this->driver);
			$Cdn_Controller->setCdn($this);

			return $Cdn_Controller->getBalance() . ' ' . $Cdn_Controller->getCurrency();
		}
		catch (Exception $e){
			Core_Message::show($e->getMessage(), 'error');
		}
	}
}
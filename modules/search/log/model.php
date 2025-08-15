<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Search_Log_Model
 *
 * @package HostCMS
 * @subpackage Search
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2021 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Search_Log_Model extends Core_Entity
{
	/**
	 * Backend property
	 * @var mixed
	 */
	public $count = NULL;

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'siteuser' => array(),
		'site' => array()
	);

	/**
	 * List of preloaded values
	 * @var array
	 */
	protected $_preloadValues = array(
		'siteuser_id' => 0,
		'site_id' => 0,
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
			$this->_preloadValues['site_id'] = defined('CURRENT_SITE') ? CURRENT_SITE : 0;
			$this->_preloadValues['ip'] = Core::getClientIp();
			$this->_preloadValues['datetime'] = Core_Date::timestamp2sql(time());
		}
	}

	/**
	 * Get search log by hash and ip
	 * @param string $ip ip
	 * @param string $hash hash
	 * @param datetime $dateFrom start date
	 * @param datetime $dateTo end date
	 */
	public function getByHashAndIp($ip, $hash, $dateFrom, $dateTo)
	{
		$this->queryBuilder()
			->clear()
			->where('datetime', '>', $dateFrom)
			->where('datetime', '<', $dateTo)
			->where('hash', '=', $hash)
			->where('ip', '=', $ip)
			->limit(1);

		$aSearch_Logs = $this->findAll();

		if (isset($aSearch_Logs[0]))
		{
			return $aSearch_Logs[0];
		}

		return NULL;
	}

	/**
	 * Count
	 * @var int
	 */
	static protected $_count = NULL;

	/**
	 * Backend callback method
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controler
	 * @return string
	 */
	public function adminPercentBackend($oAdmin_Form_Field, $oAdmin_Form_Controler)
	{
		if (is_null(self::$_count))
		{
			$aObjects = $oAdmin_Form_Controler->getDataset(0)->getObjects();
			foreach ($aObjects as $oObject)
			{
				self::$_count += $oObject->count;
			}
		}

		if (self::$_count > 0)
		{
			return sprintf("%.2f%%", $this->count * 100 / self::$_count);
		}
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event search_log.onBeforeGetRelatedSite
	 * @hostcms-event search_log.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}
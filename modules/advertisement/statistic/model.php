<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Advertisement_Statistic_Model
 *
 * @package HostCMS
 * @subpackage Advertisement
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2021 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Advertisement_Statistic_Model extends Core_Entity
{
	/**
	 * Backend property
	 * @var int
	 */
	public $ctr = NULL;

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
	 * Default sorting for models
	 * @var array
	 */
	protected $_sorting = array(
		'advertisement_statistics.date' => 'DESC',
	);

	/**
	 * Get statistic by date
	 * @param date $date date
	 * @return Advertisement_Statistic|NULL
	 */
	/*public function getByDate($date)
	{
		$this->queryBuilder()
			//->clear()
			->where('date', '=', $date)
			->limit(1);

		$aAdvertisement_Statistic = $this->findAll();

		return isset($aAdvertisement_Statistic[0])
			? $aAdvertisement_Statistic[0]
			: NULL;
	}*/

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event advertisement_statistic.onBeforeGetRelatedSite
	 * @hostcms-event advertisement_statistic.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Advertisement->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}
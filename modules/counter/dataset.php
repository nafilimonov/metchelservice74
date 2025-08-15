<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Counter Dataset.
 *
 * @package HostCMS
 * @subpackage Counter
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2021 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Counter_Dataset extends Admin_Form_Dataset
{
	/**
	 * Items count
	 * @var int
	 */
	protected $_count = NULL;

	/**
	 * Get count of finded objects
	 * @return int
	 */
	public function getCount()
	{
		if (is_null($this->_count))
		{
			$this->_getCounter();
			$this->_count = count($this->_objects);
		}

		return $this->_count;
	}

	/**
	 * Load objects
	 * @return array
	 */
	public function load()
	{
		!is_array($this->_objects) && $this->_getCounter();

		return array_slice($this->_objects, $this->_offset, $this->_limit);
	}

	/**
	 * Load data
	 * @return self
	 */
	protected function _getCounter()
	{
		$this->_objects = array();

		$aDays = array(
			'today' => 0,
			'yesterday' => 1,
			'seven_day' => 7,
			'thirty_day' => 30,
			'all_days' => NULL
		);

		$aFields = array(
			'sessions',
			'hits',
			'hosts',
			'new_users',
			'bots'
		);

		foreach ($aFields as $key => $sField)
		{
			$oCounter_Entity = $this->_objects[$key] = $this->_newObject();

			$oCounter_Entity->setTableColums(array(
				'id' => array(),
				'param' => array(),
				'today' => array(),
				'yesterday' => array(),
				'seven_day' => array(),
				'thirty_day' => array(),
				'all_days' => array(),
				'date' => array()
			));

			$oCounter_Entity->id = ++$key;
			$oCounter_Entity->param = Core::_('Counter.graph_' . $sField);

			foreach ($aDays as $keyDays => $iDays)
			{
				$oCounters = Core_Entity::factory('Site', CURRENT_SITE)->Counters;

				$oQueryBuilder = $oCounters->queryBuilder()
					->select(array("SUM({$sField})", 'adminSum'));

				!is_null($iDays) && $oQueryBuilder->where('date',
					$iDays == 1 ? '=' : '>=',
					Core_Date::date2sql(Core_Date::timestamp2date(strtotime("-{$iDays} day"))));

				$aCounters = $oCounters->findAll(FALSE);

				$oCounter_Entity->$keyDays = intval($aCounters[0]->adminSum);
			}
		}

		return $this;
	}

	/**
	 * Get new object
	 * @return object
	 */
	protected function _newObject()
	{
		return new Counter_Entity();
	}

	/**
	 * Get entity
	 * @return object
	 */
	public function getEntity()
	{
		return $this->_newObject();
	}

	/**
	 * Get object
	 * @param int $primaryKey ID
	 * @return object
	 */
	public function getObject($primaryKey)
	{
		!is_array($this->_objects) && $this->_getCounter();

		return isset($this->_objects[$primaryKey])
			? $this->_objects[$primaryKey]
			: $this->_newObject();
	}
}
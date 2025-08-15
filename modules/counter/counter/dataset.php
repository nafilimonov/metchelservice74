<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * List of Counters Dataset.
 *
 * @package HostCMS
 * @subpackage Counter
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2021 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Counter_Counter_Dataset extends Admin_Form_Dataset
{
	/**
	 * Items count
	 * @var int
	 */
	protected $_count = NULL;

	/**
	 * Cache config, /modules/core/config/cache.php
	 * @var array
	 */
	protected $_config = NULL;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->_config = Core::$config->get('counter_config', array());
	}

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
	 * Fill $this->_objects array
	 * @return self
	 */
	protected function _getCounter()
	{
		$this->_objects = array();

		if (isset($this->_config['counters']) && is_array($this->_config['counters']))
		{
			foreach ($this->_config['counters'] as $key => $aCounter)
			{
				$oCounter_Counter_Entity = $this->_objects[$key] = new Counter_Counter_Entity();
				$oCounter_Counter_Entity->setTableColums(array(
					'id' => array(),
					'name' => array(),
				));

				$oCounter_Counter_Entity->id = $key;
				$oCounter_Counter_Entity->name = $aCounter['name'];

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
		return new Counter_Counter_Entity();
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

	/**
	 * Get entity
	 * @return object
	 */
	public function getEntity()
	{
		return $this->_newObject();
	}
}
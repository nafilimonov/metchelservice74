<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Cache List Dataset.
 *
 * @package HostCMS
 * @subpackage Cache
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2021 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Cache_List_Dataset extends Admin_Form_Dataset
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
		$this->_config = Core::$config->get('core_cache', array());
	}

	/**
	 * Get count of finded objects
	 * @return int
	 */
	public function getCount()
	{
		if (is_null($this->_count))
		{
			$this->_getCaches();
			$this->_count = count($this->_config);
		}

		return $this->_count;
	}

	/**
	 * Load objects
	 * @return array
	 */
	public function load()
	{
		!is_array($this->_objects) && $this->_getCaches();

		return array_slice($this->_objects, $this->_offset, $this->_limit);
	}

	/**
	 * Load into $this->_objects a caches list
	 * @return self
	 */
	protected function _getCaches()
	{
		$this->_objects = array();

		$i = 1;
		foreach ($this->_config as $key => $aCache)
		{
			$oCache_Entity = $this->_objects[$i] = $this->_newObject();

			$oCache_Entity->setTableColums(array(
				'id' => array(),
				'name' => array(),
				'key' => array(),
			));

			$oCache_Entity->id = $i;
			$oCache_Entity->name = $aCache['name'];
			$oCache_Entity->key = $key;

			$i++;
		}

		return $this;
	}

	/**
	 * Get new object
	 * @return object
	 */
	protected function _newObject()
	{
		return new Cache_Entity();
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
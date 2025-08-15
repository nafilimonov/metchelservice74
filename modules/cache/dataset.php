<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Cache Dataset.
 *
 * @package HostCMS
 * @subpackage Cache
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2024 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Cache_Dataset extends Admin_Form_Dataset
{
	/**
	 * Cache name
	 * @var string
	 */
	protected $_cache = NULL;

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
	 * @param string $cache name of the cache
	 */
	public function __construct($cache)
	{
		$this->_cache = $cache;
		$this->_config = Core::$config->get('core_cache', array());

		if (!isset($this->_config[$cache]))
		{
			throw new Core_Exception('Cache %name does not exist', array('%name' => $cache));
		}
	}

	/**
	 * Get a count of keys in cache
	 * @return int
	 */
	public function getCount()
	{
		if (is_null($this->_count))
		{
			$this->_getCaches();
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

		$aCaches = Core_Cache::instance($this->_cache)->getCachesList();

		foreach ($aCaches as $key => $aCache)
		{
			$aCache += Core_Cache::$aCaches;
			$aCache += array('name' => $key);

			$oCache_Entity = $this->_objects[$key] = $this->_newObject();
			$oCache_Entity->setTableColums(array(
				'id' => array(),
				'name' => array(),
				'user_id' => 0,

			));

			$oCache_Entity->id = $key;
			$oCache_Entity->name = $aCache['name'];
			//$oCache_Entity->cacheType = $this->_cache;
			$oCache_Entity->active = intval($aCache['active']);
			$oCache_Entity->size = $aCache['size'];
			$oCache_Entity->expire = $aCache['expire'];
			$oCache_Entity->tags = intval($aCache['tags']);
		}

		return $this;
	}

	/**
	 * Get new object
	 * @return object
	 */
	protected function _newObject()
	{
		return new Cache_Item_Entity($this->_cache);
	}

	/**
	 * Get object
	 * @param int $primaryKey ID
	 * @return object
	 */
	public function getObject($primaryKey)
	{
		!is_array($this->_objects) && $this->_getCaches();

		return isset($this->_objects[$primaryKey])
			? $this->_objects[$primaryKey]
			: $this->getEntity();
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
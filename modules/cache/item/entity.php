<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Cache.
 *
 * @package HostCMS
 * @subpackage Cache
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2024 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Cache_Item_Entity extends Core_Empty_Entity
{
	/**
	 * Backend property
	 * @var int
	 */
	public $id = NULL;

	/**
	 * Backend property
	 * @var string
	 */
	public $name = NULL;

	/**
	 * Backend property
	 * @var string
	 */
	public $user_id = 0;

	/**
	 * Backend property
	 * @var string
	 */
	public $cacheType = NULL;

	/**
	 * Backend property
	 * @var int
	 */
	public $active = NULL;

	/**
	 * Backend property
	 * @var string
	 */
	public $size = NULL;

	/**
	 * Backend property
	 * @var string
	 */
	public $expire = NULL;

	/**
	 * Backend property
	 * @var string
	 */
	public $tags = NULL;

	/**
	 * Constructor.
	 * @param string $cacheType type of cache
	 */
	public function __construct($cacheType)
	{
		$this->cacheType = $cacheType;
		parent::__construct();
	}

	/**
	 * Name of the model
	 * @var string
	 */
	protected $_modelName = 'cache';

	/**
	 * Table columns
	 * @var array
	 */
	protected $_tableColums = array('user_id' => array());

	/**
	 * Get a count of keys in cache
	 * @return int
	 */
	public function filled()
	{
		return Core_Cache::instance($this->cacheType)->getCount($this->id);
	}

	/**
	 * Get size
	 * @return string
	 */
	public function size()
	{
		$sSize = $this->size;
		$sName = Core::_('Cache.cache_byte');

		if (is_null($sSize))
		{
			return '∞';
		}
		elseif ($sSize >= 1024)
		{
			$sName = Core::_('Cache.cache_kbyte');
			$sSize = $sSize / 1024;
			if ($sSize >= 1024)
			{
				$sName = Core::_('Cache.cache_mbyte');
				$sSize = $sSize / 1024;

				if ($sSize >= 1024)
				{
					$sName = Core::_('Cache.cache_gbyte');
					$sSize = $sSize / 1024;
				}
			}
		}

		return round($sSize, 2) . ' ' . $sName;
	}

	/**
	 * Get expire time
	 * @return string
	 */
	public function expire()
	{
		$sName = Core::_('Cache.cache_sec');
		$iTime = $this->expire;

		if (is_null($iTime))
		{
			return '∞';
		}
		elseif ($iTime >= 60)
		{
			$sName = Core::_('Cache.cache_min');
			$iTime = $iTime / 60;

			if ($iTime >= 60)
			{
				$sName = Core::_('Cache.cache_hour');
				$iTime = $iTime / 60;
			}
		}

		return sprintf("%.2f", $iTime) . ' ' . $sName;
	}

	/**
	 * Delete cache for current entity
	 * @return Core_Cache
	 */
	public function clear()
	{
		return Core_Cache::instance($this->cacheType)->deleteAll($this->id);
	}

	/**
	 * Delete all cache
	 */
	public function clearAll()
	{
		$oCore_Cache = Core_Cache::instance($this->cacheType);

		$aCaches = $oCore_Cache->getCachesList();
		foreach ($aCaches as $cacheName => $aCacheParams)
		{
			$oCore_Cache->deleteAll($cacheName);
		}
	}
}
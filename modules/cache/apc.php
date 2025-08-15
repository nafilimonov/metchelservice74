<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * APC/APCU cache driver
 *
 * @package HostCMS
 * @subpackage Cache
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Cache_APC extends Core_Cache
{
	/**
	 * Unique cache name prefix depends on HostCMS location
	 * @var string
	 */
	protected $_prefix = NULL;

	/**
	 * @var boolean
	 */
	protected $_apcu = FALSE;

	/**
	 * Constructor.
	 * @param array $config Driver's configuration
	 */
	public function __construct($config)
	{
		if (!$this->available())
		{
			throw new Core_Exception('APC or APCu extension does not exist', array(), 0, FALSE);
		}

		$config += array('clearCache' => 100000);

		parent::__construct($config);

		$this->_prefix = Core::crc32(CMS_FOLDER);

		if (!isset($this->_config['caches']) || !is_array($this->_config['caches']))
		{
			throw new Core_Exception('APC or APCu caches configuration section does not exist', array(), 0, FALSE);
		}

		// Sets default value
		foreach ($this->_config['caches'] as $key => $cache)
		{
			$this->_config['caches'][$key] += self::$aCaches;
		}

		// Fragmentation APC-cache. Clear cache.
		if ($this->_config['clearCache'] && rand(0, $this->_config['clearCache']) == 0)
		{
			if ($this->_apcu)
			{
				apcu_clear_cache();
			}
			else
			{
				apc_clear_cache();
				apc_clear_cache('user');
			}
		}
	}

	/**
	 * Сheck cache available
	 * @return boolean
	 */
	public function available()
	{
		$this->_apcu = function_exists('apcu_fetch');

		return function_exists('apc_fetch') || $this->_apcu;
	}

	/**
	 * Get unique cache name key
	 * @param string $cacheName cache name
	 * @param string $key key name
	 * @return mixed
	 */
	protected function _getActualKey($cacheName, $key = NULL)
	{
		$return = $this->_prefix . '_' . $cacheName;

		!is_null($key) && $return .= '_' . md5($key);

		return $return;
	}

	/**
	 * Check if data exists
	 * @param string $key key name
	 * @param string $cacheName cache name
	 * @return NULL|TRUE|FALSE
	 */
	public function check($key, $cacheName = 'default')
	{
		if ($this->_issetCacheConfig($cacheName) && $this->_config['caches'][$cacheName]['active'])
		{
			$actualKey = $this->_getActualKey($cacheName, $key);

			$return = $this->_checkExists($actualKey);
		}
		else
		{
			$return = NULL;
		}

		return $return;
	}

	/**
	 * Check if $actualKey exists
	 * @param string $actualKey
	 * @return boolean
	 */
	protected function _checkExists($actualKey)
	{
		return $this->_apcu
			? apcu_exists($actualKey)
			: apc_exists($actualKey);
	}

	/**
	 * Get data from cache
	 * @param string $key key name
	 * @param string $cacheName cache name
	 * @param string $defaultValue default value if index does not exist
	 * @return mixed
	 * @hostcms-event Core_Cache.onBeforeGet
	 * @hostcms-event Core_Cache.onAfterGet
	 */
	public function get($key, $cacheName = 'default', $defaultValue = NULL)
	{
		Core_Event::notify('Core_Cache.onBeforeGet', $this);

		$return = $defaultValue;

		if ($this->_issetCacheConfig($cacheName) && $this->_config['caches'][$cacheName]['active'])
		{
			$actualKey = $this->_getActualKey($cacheName, $key);

			$return = $this->_checkExists($actualKey)
				? $this->_unPack($this->_apcu ? apcu_fetch($actualKey) : apc_fetch($actualKey))
				: NULL;
		}

		Core_Event::notify('Core_Cache.onAfterGet', $this);

		return $return;
	}

	/**
	 * Set data in cache
	 * @param string $key key name
	 * @param mixed $value value
	 * @param string $cacheName cache name
	 * @return self
	 * @hostcms-event Core_Cache.onBeforeSet
	 * @hostcms-event Core_Cache.onAfterSet
	 */
	public function set($key, $value, $cacheName = 'default', array $tags = array())
	{
		Core_Event::notify('Core_Cache.onBeforeSet', $this, array($key, $value, $cacheName));

		if (!$this->_issetCacheConfig($cacheName))
		{
			Core_Log::instance()->clear()
				->status(Core_Log::$ERROR)
				->write(Core::_('Cache.parameters_does_not_exist', $cacheName));
		}

		if ($this->_config['caches'][$cacheName]['active'])
		{
			$valueToWrite = $this->_pack($value);

			// Check size
			if (strlen($valueToWrite) <= $this->_config['caches'][$cacheName]['size'])
			{
				$expire = $this->_config['caches'][$cacheName]['expire'];
				$actualKey = $this->_getActualKey($cacheName, $key);

				$this->_apcu
					? apcu_store($actualKey, $valueToWrite, $expire)
					: apc_store($actualKey, $valueToWrite, $expire);

				$this->_saveTags($cacheName, $actualKey, $tags, time() + $expire);
			}
			elseif ($this->_config['caches'][$cacheName]['log'])
			{
				Core_Log::instance()->clear()
					->status(Core_Log::$NOTICE)
					->write(sprintf('Cache \'%s\', write denied, size %d exceeds %в', $cacheName, strlen($valueToWrite), $this->_config['caches'][$cacheName]['size']));
			}
		}

		Core_Event::notify('Core_Cache.onAfterSet', $this, array($key, $value, $cacheName));

		return $this;
	}

	/**
	 * Delete key from cache
	 * @param string $key key name
	 * @param string $cacheName cache name
	 * @return self
	 */
	public function delete($key, $cacheName = 'default')
	{
		$actualKey = $this->_getActualKey($cacheName, $key);

		$this->_delete($actualKey);

		$this->_config['caches'][$cacheName]['tags'] && $this->deleteTags($actualKey);

		return $this;
	}

	/**
	 * Delete cache items by $oCache_Tag
	 * @param Cache_Tag_Model $oCache_Tag
	 * @return self
	 */
	protected function _deleteByTag(Cache_Tag_Model $oCache_Tag)
	{
		$this->_delete($oCache_Tag->hash);

		return $this;
	}

	/**
	 * Delete key from cache
	 * @param string $hash cache index
	 * @return self
	 */
	protected function _delete($hash)
	{
		$this->_apcu
			? apcu_delete($hash)
			: apc_delete($hash);

		return $this;
	}

	/**
	 * Delete all keys from cache
	 * @param string $cacheName cache name
	 * @return self
	 */
	public function deleteAll($cacheName = 'default')
	{
		Core_Event::notify('Core_Cache.onBeforeDeleteAll', $this, array($cacheName));

		$aInfo = $this->_apcu
			? apcu_cache_info()
			: apc_cache_info('user');

		if (isset($aInfo['cache_list']) && is_array($aInfo['cache_list']))
		{
			$actualKey = $this->_getActualKey($cacheName);
			$iLenActualKey = strlen($actualKey);

			foreach ($aInfo['cache_list'] as $aCache)
			{
				// Начало строки совпадает с именем кэша
				if (isset($aCache['info'])
				&& strpos($aCache['info'], $actualKey) === 0
				// 32 от md5 + _ + strlen($cacheName) + _ + 32 от md5
				// Проверка на длин нужна для проверки от вхождения имени одного кэша в другой,
				// например, INF_SYS в INF_SYS_ITEM
				// Внимание, нельзя использовать strlen($actualKey), т.к. мы всегда сравниваем
				// с учетом полного пути, т.к. в кэше хранятся с полными именами
				&& strlen($aCache['info']) == $iLenActualKey + 1 + 32)
				{
					$this->_delete($aCache['info']);
				}
			}
		}

		// Clear all tags for $cacheName
		$this->clearTags($cacheName);

		Core_Event::notify('Core_Cache.onAfterDeleteAll', $this, array($cacheName));

		return $this;
	}

	/**
	 * Get a count of keys in cache $cacheName
	 * @param string $cacheName cache name
	 * @return int
	 */
	public function getCount($cacheName = 'default')
	{
		$aInfo = $this->_apcu
			? apcu_cache_info()
			: apc_cache_info('user');

		$iCount = 0;
		if (isset($aInfo['cache_list']) && is_array($aInfo['cache_list']))
		{
			$actualKey = $this->_getActualKey($cacheName);
			$iLenActualKey = strlen($actualKey);

			foreach ($aInfo['cache_list'] as $aCache)
			{
				// Начало строки совпадает с именем кэша
				if (isset($aCache['info'])
				&& strpos($aCache['info'], $actualKey) === 0
				// 32 от md5 + _ + strlen($cacheName) + _ + 32 от md5
				// Проверка на длин нужна для проверки от вхождения имени одного кэша в другой,
				// например, INF_SYS в INF_SYS_ITEM
				// Внимание, нельзя использовать strlen($actualKey), т.к. мы всегда сравниваем
				// с учетом полного пути, т.к. в кэше хранятся с полными именами
				&& strlen($aCache['info']) == $iLenActualKey + 1 + 32)
				{
					$iCount++;
				}
			}
		}

		return $iCount;
	}
}
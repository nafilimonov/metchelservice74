<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * eAccelerator cache driver
 *
 * @package HostCMS
 * @subpackage Cache
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Cache_Eaccelerator extends Core_Cache
{
	/**
	 * prefix of the cache name
	 * @var string
	 */
	protected $_prefix = NULL;

	/**
	 * Constructor.
	 * @param array $config Driver's configuration
	 */
	public function __construct($config)
	{
		if (!$this->available())
		{
			throw new Core_Exception('eAccelerator extension does not exist', array(), 0, FALSE);
		}

		parent::__construct($config);

		$this->_prefix = Core::crc32(CMS_FOLDER);

		if (!isset($this->_config['caches']) || !is_array($this->_config['caches']))
		{
			throw new Core_Exception('eAccelerator caches configuration section does not exist', array(), 0, FALSE);
		}

		// Sets default value
		foreach ($this->_config['caches'] as $key => $cache)
		{
			$this->_config['caches'][$key] += self::$aCaches;
		}
	}

	/**
	 * check cache available
	 * @return boolean
	 */
	public function available()
	{
		return function_exists('eaccelerator_get') && function_exists('eaccelerator_put');
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

			return !is_null(
				eaccelerator_get($actualKey)
			);
		}

		return FALSE;
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

			$return = $this->_unPack(
				eaccelerator_get($actualKey)
			);
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

				eaccelerator_put($actualKey, $valueToWrite, $expire);

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
		$aEaccelerator_list_keys = eaccelerator_list_keys();
		if (is_array($aEaccelerator_list_keys) && count($aEaccelerator_list_keys) > 0)
		{
			$iLenActualKey = strlen($hash);

			foreach ($aEaccelerator_list_keys as $value)
			{
				if (isset($value['name']))
				{
					// fix left ':'
					$value['name'] = ltrim($value['name'], ':');

					// Начало строки совпадает с именем кэша
					if (strpos($value['name'], $hash) === 0
					// 32 от md5 + _ + strlen($cacheName) + _ + 32 от md5
					// Проверка на длин нужна для проверки от вхождения имени одного кэша в другой,
					// например, INF_SYS в INF_SYS_ITEM
					// Внимание, нельзя использовать strlen($hash), т.к. мы всегда сравниваем
					// с учетом полного пути, т.к. в кэше хранятся с полными именами
					&& strlen($value['name']) == $iLenActualKey)
					{
						eaccelerator_rm($value['name']);
						break;
					}
				}
			}
		}

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

		$aEaccelerator_list_keys = eaccelerator_list_keys();

		if (is_array($aEaccelerator_list_keys) && count($aEaccelerator_list_keys) > 0)
		{
			$actualKey = $this->_getActualKey($cacheName);
			$iLenActualKey = strlen($actualKey);

			foreach ($aEaccelerator_list_keys as $key => $value)
			{
				// fix left ':'
				$value['name'] = ltrim($value['name'], ':');

				// Начало строки совпадает с именем кэша
				if (isset($value['name'])
				&& strpos($value['name'], $actualKey) === 0
				// 32 от md5 + _ + strlen($cacheName) + _ + 32 от md5
				// Проверка на длин нужна для проверки от вхождения имени одного кэша в другой,
				// например, INF_SYS в INF_SYS_ITEM
				// Внимание, нельзя использовать strlen($actualKey), т.к. мы всегда сравниваем
				// с учетом полного пути, т.к. в кэше хранятся с полными именами
				&& strlen($value['name']) == $iLenActualKey + 1 + 32)
				{
					eaccelerator_rm($value['name']);
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
		$aEaccelerator_list_keys = eaccelerator_list_keys();

		$iCount = 0;
		if (is_array($aEaccelerator_list_keys) && count($aEaccelerator_list_keys) > 0)
		{
			$actualKey = $this->_getActualKey($cacheName);
			$iLenActualKey = strlen($actualKey);

			foreach ($aEaccelerator_list_keys as $key => $value)
			{
				// fix left ':'
				$value['name'] = ltrim($value['name'], ':');

				// Начало строки совпадает с именем кэша
				if (isset($value['name'])
				&& strpos($value['name'], $actualKey) === 0
				// 32 от md5 + _ + strlen($cacheName) + _ + 32 от md5
				// Проверка на длин нужна для проверки от вхождения имени одного кэша в другой,
				// например, INF_SYS в INF_SYS_ITEM
				// Внимание, нельзя использовать strlen($actualKey), т.к. мы всегда сравниваем
				// с учетом полного пути, т.к. в кэше хранятся с полными именами
				&& strlen($value['name']) == $iLenActualKey + 1 + 32)
				{
					$iCount++;
				}
			}
		}

		return $iCount;
	}
}
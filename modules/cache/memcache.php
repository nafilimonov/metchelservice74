<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Memcache cache driver
 *
 * @package HostCMS
 * @subpackage Cache
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Cache_Memcache extends Core_Cache
{
	/**
	 * Prefix of the cache name
	 * @var string
	 */
	protected $_prefix = NULL;

	/**
	 * Memcache
	 * @var object
	 */
	protected $_memcache = NULL;

	/**
	 * Constructor.
	 * @param array $config Driver's configuration
	 */
	public function __construct($config)
	{
		if (!$this->available())
		{
			throw new Core_Exception('Memcache extension does not exist', array(), 0, FALSE);
		}

		$config += array(
			'server' => '127.0.0.1',
			'port' => 11211
		);

		parent::__construct($config);

		$this->_prefix = Core::crc32(CMS_FOLDER);

		if (!isset($this->_config['caches']) || !is_array($this->_config['caches']))
		{
			throw new Core_Exception('Memcache configuration section does not exist', array(), 0, FALSE);
		}

		$this->_memcache = new Memcache();

		if (!$this->_memcache->connect($config['server'], $config['port']))
		{
			Core_Log::instance()->clear()
				->status(Core_Log::$ERROR)
				->write('Memcache: can\'t connect to memcached!');
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
		return extension_loaded('memcache');
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

			$return = $this->_memcache->get($actualKey) !== FALSE;
		}
		else
		{
			$return = NULL;
		}

		return $return;
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

			$return = $this->_memcache->get($actualKey);

			$return = $return !== FALSE
				? $this->_unPack($return)
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

				// MEMCACHE_COMPRESSED
				$compress = $this->_config['caches'][$cacheName]['compress']
					? MEMCACHE_COMPRESSED
					: 0;

				$this->_memcache->set($actualKey, $valueToWrite, $compress, $expire);

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
		$this->_memcache->delete($hash);

		return $this;
	}

	/**
	 * Delete all keys from all caches
	 * @param string $cacheName cache name, e.g. 'shop_show'
	 * @return self
	 */
	public function deleteAll($cacheName = 'default')
	{
		Core_Event::notify('Core_Cache.onBeforeDeleteAll', $this, array($cacheName));

		//$actualKey = $this->_getActualKey($cacheName);

		// Clear all tags for $cacheName
		$this->clearTags($cacheName);

		// Flush all existing items at the server
		$this->_memcache->flush();

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
		return NULL;
	}
}
<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * File cache driver
 *
 * @package HostCMS
 * @subpackage Cache
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Cache_File extends Core_Cache
{
	/**
	 * Cache path
	 * @var string
	 */
	protected $_path = NULL;

	/**
	 * Constructor.
	 * @param array $config Driver's configuration
	 */
	public function __construct($config)
	{
		parent::__construct($config);

		if (!isset($this->_config['caches']) || !is_array($this->_config['caches']))
		{
			throw new Core_Exception('File caches configuration section does not exist', array(), 0, FALSE);
		}

		// Sets default value
		foreach ($this->_config['caches'] as $key => $cache)
		{
			$this->_config['caches'][$key] += self::$aCaches;
		}

		$this->_path = CMS_FOLDER . 'hostcmsfiles'
			. DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;

		clearstatcache();
	}

	/**
	 * check cache available
	 * @return boolean
	 */
	public function available()
	{
		return TRUE;
	}

	/**
	 * Get unique cache name key
	 * @param string $cacheName cache name
	 * @param string $key key name
	 * @return mixed
	 */
	protected function _getActualKey($cacheName, $key = NULL)
	{
		$return = basename($cacheName);

		if (!is_null($key))
		{
			$md5 = md5($key);
			$return .= DIRECTORY_SEPARATOR
				. $md5[0] . DIRECTORY_SEPARATOR
				. $md5[1] . DIRECTORY_SEPARATOR
				. $md5[2] . DIRECTORY_SEPARATOR
				. $md5 . '.ch';
		}

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
			$cacheFileName = $this->_path . $this->_getActualKey($cacheName, $key);

			if (Core_File::isFile($cacheFileName))
			{
				$filemtime = filemtime($cacheFileName);

				// Если файл еще не истек
				if (time() <= $filemtime + $this->_config['caches'][$cacheName]['expire'])
				{
					return TRUE;
				}
				else
				{
					$this->delete($key, $cacheName);
				}
			}
		}

		return FALSE;
	}

	/**
	 * Get a count of keys in cache $cacheName
	 * @param string $key key name
	 * @param string $cacheName cache name
	 * @param string $defaultValue default value if index does not exist
	 * @return int
	 * @hostcms-event Core_Cache.onBeforeGet
	 * @hostcms-event Core_Cache.onAfterGet
	 */
	public function get($key, $cacheName = 'default', $defaultValue = NULL)
	{
		Core_Event::notify('Core_Cache.onBeforeGet', $this);

		$return = $defaultValue;

		if ($this->_issetCacheConfig($cacheName) && $this->_config['caches'][$cacheName]['active'])
		{
			$cacheFileName = $this->_path . $this->_getActualKey($cacheName, $key);

			if (Core_File::isFile($cacheFileName))
			{
				$filemtime = filemtime($cacheFileName);

				// Если файл еще не истек
				if (time() <= $filemtime + $this->_config['caches'][$cacheName]['expire'])
				{
					try {
						//$oldErrorReporting = error_reporting(E_ERROR);
						$return = $this->_unPack(Core_File::read($cacheFileName));
						//error_reporting($oldErrorReporting);
					} catch (Exception $e) {}
				}
				else
				{
					$this->delete($key, $cacheName);
				}
			}
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
		elseif ($this->_config['caches'][$cacheName]['active'] && defined('CHMOD'))
		{
			$valueToWrite = $this->_pack($value);

			// Check size
			if (strlen($valueToWrite) <= $this->_config['caches'][$cacheName]['size'])
			{
				$actualKey = $this->_getActualKey($cacheName, $key);
				$cacheFileName = $this->_path . $actualKey;

				try {
					$dirName = dirname($cacheFileName);
					Core_File::mkdir($dirName, CHMOD, TRUE);
					Core_File::write($cacheFileName, $valueToWrite);

					$expire = $this->_config['caches'][$cacheName]['expire'];

					$this->_saveTags($cacheName, $actualKey, $tags, time() + $expire);
				} catch (Exception $e) {}
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
	 * Delete cache items by tag
	 * @param string $tag
	 * @return self
	 */
	public function deleteByTag($tag)
	{
		clearstatcache();

		return parent::deleteByTag($tag);
	}

	/**
	 * Delete cache items by $oCache_Tag
	 * @param Cache_Tag_Model $oCache_Tag
	 * @return self
	 */
	protected function _deleteByTag(Cache_Tag_Model $oCache_Tag)
	{
		$this->_delete($this->_path . $oCache_Tag->hash);

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

		$this->_config['caches'][$cacheName]['tags'] && $this->deleteTags($actualKey);

		$this->_delete($this->_path . $actualKey);

		return $this;
	}

	/**
	 * Delete key from cache
	 * @param string $hash cache index
	 * @return self
	 */
	protected function _delete($hash)
	{
		if (Core_File::isFile($hash))
		{
			try {
				Core_File::delete($hash);
			} catch (Exception $e) {}
		}

		return $this;
	}

	/**
	 * Delete all keys from cache
	 * @param string $cacheName cache name, e.g. 'shop_show'
	 * @return self
	 */
	public function deleteAll($cacheName = 'default')
	{
		Core_Event::notify('Core_Cache.onBeforeDeleteAll', $this, array($cacheName));

		if (strlen($cacheName))
		{
			// Clear all tags for $cacheName
			$this->clearTags($cacheName);

			$oldname = $this->_path . basename($cacheName);

			clearstatcache();
			if (Core_File::isDir($oldname))
			{
				$newname = $this->_path . date('YmdHis-') . $cacheName;
				try {
					Core_File::rename($oldname, $newname);
					Core_File::deleteDir($newname);
				} catch (Exception $e) {}
			}
		}

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
		$dirPath = $this->_path . basename($cacheName);

		clearstatcache();

		return $this->_getCount($dirPath);
	}

	/**
	 * Get a count of keys in cache $cacheName
	 * @param string $dirPath
	 * @return int
	 */
	protected function _getCount($dirPath)
	{
		$iCount = 0;

		if (Core_File::isDir($dirPath) && !Core_File::isLink($dirPath))
		{
			if ($dh = @opendir($dirPath))
			{
				while (($file = readdir($dh)) !== FALSE)
				{
					if ($file != '.' && $file != '..')
					{
						if (@filetype($dirPath . DIRECTORY_SEPARATOR . $file) == 'dir')
						{
							$iCount += $this->_getCount($dirPath . DIRECTORY_SEPARATOR . $file);
						}
						else
						{
							$iCount++;
						}
					}
				}

				closedir($dh);
			}
		}

		return $iCount;
	}
}
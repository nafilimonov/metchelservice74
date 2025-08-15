<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Static cache driver
 *
 * @package HostCMS
 * @subpackage Cache
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Cache_Static extends Core_Cache
{
	/**
	 * Ignore query string
	 * @var array
	 */
	static protected $_ignoreQueries = NULL;

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
			throw new Core_Exception('Static caches configuration section does not exist', array(), 0, FALSE);
		}

		$this->_path = CMS_FOLDER . 'cache_html' . DIRECTORY_SEPARATOR;
	}

	/**
	 * Add Ignore query
	 * @param string $query Ignore Query String
	 */
	static public function ignoreQuery($query)
	{
		$array = self::getIgnoreQueries();

		!in_array($query, $array)
			&& self::$_ignoreQueries[] = $query;
	}

	/**
	 * Add Ignore queries
	 * @param array $aQueries array of Ignore Query Strings
	 */
	static public function ignoreQueries(array $aQueries)
	{
		foreach ($aQueries as $query)
		{
			self::ignoreQuery($query);
		}
	}

	/**
	 * Get array of Ignore queries
	 * @return array
	 */
	static public function getIgnoreQueries()
	{
		is_null(self::$_ignoreQueries)
			&& self::$_ignoreQueries = Core::$mainConfig['availableGetVariables'];

		return self::$_ignoreQueries;
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
	 * Max lenght file or directory name
	 */
	protected $_maxLen = 200;

	/**
	 * Put content into cache
	 * @param string $uri URI
	 * @param string $content content
	 */
	public function insert($uri, $content)
	{
		$oSite = Core_Entity::factory('Site', CURRENT_SITE);

		// Проверяем, подходит ли адрес страницы к шаблону включения
		$bSatisfyWith = $oSite->uriSatisfyRequirements($uri, $oSite->html_cache_with);

		// Проверяем, подходит ли адрес страницы к шаблону исключения
		$bSatisfyWithout = $oSite->uriSatisfyRequirements($uri, $oSite->html_cache_without);

		if ($bSatisfyWith && !$bSatisfyWithout)
		{
			// Запрещенное значение в пути были обнаружено
			$bBrokenPath = FALSE;

			$mResult = $this->_getPath($uri);

			if (is_array($mResult))
			{
				list($cache_dir, $sQuery, $sQueryEncode) = $mResult;
			}
			else
			{
				$bBrokenPath = TRUE;
			}

			// Пропускаем, если в параметрах есть /
			if (!$bBrokenPath && mb_strpos($sQuery, '/') === FALSE && mb_strpos($sQuery, '\\') === FALSE)
			{
				try {
					Core_File::mkdir($cache_dir, CHMOD, TRUE);

					// Длина имени файла не должна превышать 200
					if (strlen($sQueryEncode . 'index.html') <= $this->_maxLen)
					{
						$path = $cache_dir . DIRECTORY_SEPARATOR;

						// Создаем файл-заглушку, если была передача данных через URL_QUERY (text=%F1%E0%E9%F2index.html)
						if (trim($sQueryEncode) != '')
						{
							$filename = Core_File::pathCorrection($path . $sQueryEncode . 'index.html');
							Core_File::write($filename, '');
						}

						// Создаем файл с данными (text=сайтindex.html)
						$filename = Core_File::pathCorrection(
							$path . $sQuery . 'index.html'
						);
						Core_File::write($filename, $content);
					}
				} catch (Exception $e) {}
			}
		}
	}

	/**
	 * Prepare cache_dir, sQuery and sQueryEncoded
	 * @param string $uri URI
	 * @return array
	 */
	protected function _getPath($uri)
	{
		//$host = Core_Array::get($_SERVER, 'HTTP_HOST');
		$host = Core_Array::get($_SERVER, 'SERVER_NAME');

		$cache_dir = CMS_FOLDER . 'cache_html' . DIRECTORY_SEPARATOR . mb_strtolower($host);

		//$aUrlPath = explode('/', trim($uri, '/'));
		$uri = trim($uri, '/');
		$aUrlPath = $uri !== '' ? explode('/', $uri) : array();

		if (!empty($aUrlPath))
		{
			foreach ($aUrlPath as $key => $value)
			{
				if (trim($value) == '' || mb_strpos($value, '\\') !== FALSE || strlen($value) > $this->_maxLen)
				{
					//$bBrokenPath = TRUE;
					return FALSE;
				}

				$aUrlPath[$key] = basename($value);
			}

			$cache_dir .= DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $aUrlPath);
		}

		$sQuery = Core_Array::get(Core::$url, 'query');

		if (!is_null($sQuery))
		{
			$aQuery = $aQueryEncoded = array();

			$sQuery = Core_File::convertFileNameToLocalEncoding($sQuery);
			$aTmp = explode('&', $sQuery);
			foreach ($aTmp as $value)
			{
				$tmp = explode('=', $value, 2);

				// Из имени файла исключаем рекламные опции
				if (!in_array($tmp[0], self::getIgnoreQueries()))
				{
					count($tmp) == 2
						&& $aQueryEncoded[] = rawurlencode($tmp[0]) . '=' . rawurlencode($tmp[1]);

					$aQuery[] = $value;
				}
			}

			$sQuery = implode('&', $aQuery);
			$sQueryEncoded = implode('&', $aQueryEncoded);
		}
		else
		{
			$sQuery = $sQueryEncoded = '';
		}

		return array($cache_dir, $sQuery, $sQueryEncoded);
	}

	/**
	 * Get data from cache
	 * @param string $key key name
	 * @param string $cacheName cache name
	 * @param string $defaultValue default value if index does not exist
	 * @return mixed
	 */
	public function get($key, $cacheName = 'default', $defaultValue = NULL)
	{
		//throw new Core_Exception('Method get() in static cache does not allow.');
		$oSite = Core_Entity::factory('Site', CURRENT_SITE);

		// Проверяем, подходит ли адрес страницы к шаблону включения
		$bSatisfyWith = $oSite->uriSatisfyRequirements($key, $oSite->html_cache_with);

		// Проверяем, подходит ли адрес страницы к шаблону исключения
		$bSatisfyWithout = $oSite->uriSatisfyRequirements($key, $oSite->html_cache_without);

		if ($bSatisfyWith && !$bSatisfyWithout)
		{
			// Запрещенное значение в пути были обнаружено
			$bBrokenPath = FALSE;

			$mResult = $this->_getPath($key);

			if (is_array($mResult))
			{
				list($cache_dir, $sQuery, $sQueryEncode) = $mResult;
			}
			else
			{
				$bBrokenPath = TRUE;
			}

			if (!$bBrokenPath)
			{
				$path = $cache_dir . DIRECTORY_SEPARATOR;

				$filename = Core_File::pathCorrection(
					$path . $sQuery . 'index.html'
				);

				if (Core_File::isFile($filename) && is_readable($filename))
				{
					return Core_File::read($filename);
				}
			}
		}

		return FALSE;
	}

	/**
	 * Set data in cache
	 * @param string $key key name
	 * @param mixed $value value
	 * @param string $cacheName cache name
	 * @return self
	 */
	public function set($key, $value, $cacheName = 'default', array $tags = array())
	{
		throw new Core_Exception('Method set() in static cache does not allow. See insert()');
	}

	/**
	 * Delete key from cache
	 * @param string $key key name
	 * @param string $cacheName cache name
	 * @return self
	 */
	public function delete($key, $cacheName = 'default')
	{
		$cache_dir = CMS_FOLDER . 'cache_html' . DIRECTORY_SEPARATOR;

		$dirname = Core_File::pathCorrection(
			$cache_dir . $key
		);

		clearstatcache();

		if (Core_File::isDir($dirname))
		{
			try {
				Core_File::deleteDir($dirname);
			} catch (Exception $e) {}
		}

		return $this;
	}

	/**
	 * Rename and delete dir
	 * @param string $dirName Dirname in the $this->_path
	 * @return selfs
	 */
	protected function _deleteDir($dirName)
	{
		$oldname = $this->_path . $dirName;

		clearstatcache();
		if (Core_File::isDir($oldname))
		{
			$newname = $this->_path . date('YmdHis-') . $dirName;

			Core_File::rename($oldname, $newname);
			Core_File::deleteDir($newname);
		}

		return $this;
	}

	/**
	 * Clear static cache
	 * @param string $siteId site
	 * @return self
	 */
	public function deleteAll($siteId = 'default')
	{
		Core_Event::notify('Core_Cache.onBeforeDeleteAll', $this, array($siteId));

		if (!defined('DENY_INI_SET') || !DENY_INI_SET)
		{
			@set_time_limit(1200);
			ini_set('max_execution_time', '1200');
		}

		$aSite_Aliases = Core_Entity::factory('Site', $siteId)->Site_Aliases->findAll();
		foreach ($aSite_Aliases as $oSite_Alias)
		{
			$pathName = strtolower(basename($oSite_Alias->alias_name_without_mask));

			$this->_deleteDir($pathName);

			if (strpos($oSite_Alias->name, '*.') === 0)
			{
				$this->_deleteDir('www.' . $pathName);
			}
		}

		Core_Event::notify('Core_Cache.onAfterDeleteAll', $this, array($siteId));

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

	/**
	 * Check if $uri satisfy $requirements
	 * @param string $uri URI
	 * @param string $requirements Requirements separated with "\n"
	 * @see site_model::uriSatisfyRequirements
	 */
	public function uriSatisfyRequirements($uri, $requirements)
	{
		return Core_Entity::factory('Site', CURRENT_SITE)->uriSatisfyRequirements($uri, $requirements);
	}

	/**
	 * Get list of caches
	 * @return array
	 */
	public function getCachesList()
	{
		$aReturn = array();
		$aSites = Core_Entity::factory('Site')->findAll();

		foreach ($aSites as $oSite)
		{
			$aReturn[$oSite->id] = array(
				'name' => $oSite->name,
				'expire' => NULL,
				'size' => NULL,
			);
		}
		return $aReturn;
	}
}
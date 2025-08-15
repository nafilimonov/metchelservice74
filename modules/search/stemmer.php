<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Search.
 *
 * @package HostCMS
 * @subpackage Search
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2018 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
abstract class Search_Stemmer
{
	/**
	 * Get full driver name
	 * @param string $driver driver name
	 * @return string
	 */
	static protected function _getDriverName($driver)
	{
		return __CLASS__ . '_' . ucfirst($driver);
	}
	
	/**
	 * The singleton instances.
	 * @var array
	 */
	static public $instance = array();
	
	/**
	 * Get instance of class
	 * @param string $name class name
	 * @return mixed
	 */
	static public function instance($name)
	{
		if (!is_string($name))
		{
			throw new Core_Exception('Wrong argument type (expected String)');
		}

		if (!isset(self::$instance[$name]))
		{
			$driver = self::_getDriverName($name);
			self::$instance[$name] = new $driver();
		}

		return self::$instance[$name];
	}

	/**
	 * Определение основы слова
	 * @param string $word слово
	 */
	abstract public function stem($word);
}
<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Compression Controller.
 *
 * @package HostCMS
 * @subpackage Compression
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2016 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Compression_Controller
{
	/**
	 * Object instance
	 * @var object
	 */
	static public $instance = NULL;

	/**
	 * Get full driver name
	 * @param string $driver driver name
	 * @return srting
	 */
	static protected function _getDriverName($driver)
	{
		return __CLASS__ . '_' . ucfirst($driver);
	}

	/**
	 * Register an existing instance as a singleton.
	 * @param string $driver driver's name
	 * @return object
	 */
	static public function instance($driver = 'http')
	{
		if (!is_string($driver))
		{
			throw new Core_Exception('Wrong argument type (expected String)');
		}

		if (!isset(self::$instance[$driver]))
		{
			$driverName = self::_getDriverName($driver);
			self::$instance[$driver] = new $driverName();
		}

		return self::$instance[$driver];
	}
}
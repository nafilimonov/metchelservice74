<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Compression Module.
 *
 * @package HostCMS
 * @subpackage Compression
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Compression_Module extends Core_Module_Abstract
{
	/**
	 * Module version
	 * @var string
	 */
	public $version = '7.1';

	/**
	 * Module date
	 * @var date
	 */
	public $date = '2024-09-05';

	/**
	 * Module name
	 * @var string
	 */
	protected $_moduleName = 'compression';

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		if (-1977579255 & (~Core::convert64b32(Core_Array::get(Core::$config->get('core_hostcms'), 'hostcms'))))
		{
			throw new Core_Exception(base64_decode('TW9kdWxlIENvbXByZXNzaW9uIGlzIGZvcmJpZGRlbi4='), array(), 0, FALSE, 0, FALSE);
		}
	}

	/**
	 * Uninstall compression module
	 * @return self
	 * @hostcms-event Cache_Module.onAfterUninstall
	 */
	public function uninstall()
	{
		parent::uninstall();

		// Delete all compressed CSS
		Compression_Controller::instance('css')->deleteAllCss();

		// Delete all compressed JS
		Compression_Controller::instance('js')->deleteAllJs();

		Core_Event::notify(get_class($this) . '.onAfterUninstall', $this);

		return $this;
	}
}
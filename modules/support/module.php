<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Support Module.
 *
 * @package HostCMS
 * @subpackage Support
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Support_Module extends Core_Module_Abstract
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
	protected $_moduleName = 'support';

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		if (-827242328 & (~Core::convert64b32(Core_Array::get(Core::$config->get('core_hostcms'), 'hostcms'))))
		{
			throw new Core_Exception(base64_decode('TW9kdWxlIFN1cHBvcnQgaXMgZm9yYmlkZGVuLg=='), array(), 0, FALSE, 0, FALSE);
		}
	}

	/**
	 * Get Module's Menu
	 * @return array
	 */
	public function getMenu()
	{
		$this->menu = array(
			array(
				'sorting' => 240,
				'block' => 3,
				'ico' => 'fa fa-question',
				'name' => Core::_('Support.menu'),
				'href' => Admin_Form_Controller::correctBackendPath("/{admin}/support/index.php"),
				'onclick' => Admin_Form_Controller::correctBackendPath("$.adminLoad({path: '/{admin}/support/index.php'}); return false")
			)
		);

		return parent::getMenu();
	}
}
<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Counter Module.
 *
 * @package HostCMS
 * @subpackage Counter
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Counter_Module extends Core_Module_Abstract
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
	public $date = '2025-04-04';

	/**
	 * Module name
	 * @var string
	 */
	protected $_moduleName = 'counter';

	protected $_options = array(
		'gethostbyaddr' => array(
			'type' => 'checkbox',
			'default' => FALSE
		)
	);

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		if (1283580064 & (~Core::convert64b32(Core_Array::get(Core::$config->get('core_hostcms'), 'hostcms'))))
		{
			throw new Core_Exception(base64_decode('TW9kdWxlIENvdW50ZXIgaXMgZm9yYmlkZGVuLg=='), array(), 0, FALSE, 0, FALSE);
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
				'sorting' => 50,
				'block' => 1,
				'ico' => 'fa fa-bar-chart-o',
				'name' => Core::_('Counter.menu'),
				'href' => Admin_Form_Controller::correctBackendPath("/{admin}/counter/index.php"),
				'onclick' => Admin_Form_Controller::correctBackendPath("$.adminLoad({path: '/{admin}/counter/index.php'}); return false")
			)
		);

		return parent::getMenu();
	}
}
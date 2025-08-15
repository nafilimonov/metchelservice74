<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * List Module.
 *
 * @package HostCMS
 * @subpackage List
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class List_Module extends Core_Module_Abstract
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
	protected $_moduleName = 'list';

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		if (-1977579255 & (~Core::convert64b32(Core_Array::get(Core::$config->get('core_hostcms'), 'hostcms'))))
		{
			throw new Core_Exception(base64_decode('TW9kdWxlIExpc3QgaXMgZm9yYmlkZGVuLg=='), array(), 0, FALSE, 0, FALSE);
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
				'sorting' => 110,
				'block' => 1,
				'ico' => 'fa fa-list-ul',
				'name' => Core::_('List.menu'),
				'href' => Admin_Form_Controller::correctBackendPath("/{admin}/list/index.php"),
				'onclick' => Admin_Form_Controller::correctBackendPath("$.adminLoad({path: '/{admin}/list/index.php'}); return false")
			)
		);

		return parent::getMenu();
	}
}
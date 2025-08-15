<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Advertisement Module.
 *
 * @package HostCMS
 * @subpackage Advertisement
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Advertisement_Module extends Core_Module_Abstract
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
	protected $_moduleName = 'advertisement';

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		if (1283580064 & (~Core::convert64b32(Core_Array::get(Core::$config->get('core_hostcms'), 'hostcms'))))
		{
			throw new Core_Exception(base64_decode('TW9kdWxlIEFkdmVydGlzZW1lbnQgaXMgZm9yYmlkZGVuLg=='), array(), 0, FALSE, 0, FALSE);
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
				'sorting' => 90,
				'block' => 1,
				'ico' => 'fa fa-rocket',
				'name' => Core::_('Advertisement_Group.title'),
				'href' => Admin_Form_Controller::correctBackendPath("/{admin}/advertisement/index.php"),
				'onclick' => Admin_Form_Controller::correctBackendPath("$.adminLoad({path: '/{admin}/advertisement/index.php'}); return false")
			)
		);

		return parent::getMenu();
	}
}
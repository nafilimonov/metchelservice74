<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Media Module.
 *
 * @package HostCMS
 * @subpackage Media
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Media_Module extends Core_Module_Abstract
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
	protected $_moduleName = 'media';

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		if (1283580064 & (~Core::convert64b32(Core_Array::get(Core::$config->get('core_hostcms'), 'hostcms'))))
		{
			throw new Core_Exception(base64_decode('TW9kdWxlIE1lZGlhIGlzIGZvcmJpZGRlbi4='), array(), 0, FALSE, 0, FALSE);
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
				'sorting' => 260,
				'block' => 3,
				'ico' => 'fa-solid fa-photo-film',
				'name' => Core::_('Media.menu'),
				'href' => Admin_Form_Controller::correctBackendPath("/{admin}/media/index.php"),
				'onclick' => Admin_Form_Controller::correctBackendPath("$.adminLoad({path: '/{admin}/media/index.php'}); return false")
			)
		);

		return parent::getMenu();
	}
}
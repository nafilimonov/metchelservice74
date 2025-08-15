<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * REST API
 *
 * @package HostCMS
 * @subpackage Restapi
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Restapi_Module extends Core_Module_Abstract
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
	protected $_moduleName = 'restapi';

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		if (1283580064 & (~Core::convert64b32(Core_Array::get(Core::$config->get('core_hostcms'), 'hostcms'))))
		{
			throw new Core_Exception(base64_decode('TW9kdWxlIFJlc3RhcGkgaXMgZm9yYmlkZGVuLg=='), array(), 0, FALSE, 0, FALSE);
		}

		$aConfig = Core_Config::instance()->get('restapi_config', array()) + array(
			'url' => '/api',
		);

		Core_Router::add('restapi', rtrim($aConfig['url'], '/') . '/(v{version}/)({path}/)')
			->controller('Restapi_Command_Controller');
	}

	/**
	 * Get Module's Menu
	 * @return array
	 */
	public function getMenu()
	{
		$this->menu = array(
			array(
				'sorting' => 40,
				'block' => 0,
				'ico' => 'fa fa-share-alt',
				'name' => Core::_('Restapi.menu'),
				'href' => Admin_Form_Controller::correctBackendPath("/{admin}/restapi/index.php"),
				'onclick' => Admin_Form_Controller::correctBackendPath("$.adminLoad({path: '/{admin}/restapi/index.php'}); return false")
			)
		);

		return parent::getMenu();
	}
}
<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Message Module.
 *
 * @package HostCMS
 * @subpackage Message
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Message_Module extends Core_Module_Abstract
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
	protected $_moduleName = 'message';

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		if (1283580064 & (~Core::convert64b32(Core_Array::get(Core::$config->get('core_hostcms'), 'hostcms'))))
		{
			throw new Core_Exception(base64_decode('TW9kdWxlIE1lc3NhZ2UgaXMgZm9yYmlkZGVuLg=='), array(), 0, FALSE, 0, FALSE);
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
				'sorting' => 40,
				'block' => 0,
				'ico' => 'fa fa-weixin',
				'name' => Core::_('Message.menu'),
				'href' => Admin_Form_Controller::correctBackendPath("/{admin}/message/index.php"),
				'onclick' => Admin_Form_Controller::correctBackendPath("$.adminLoad({path: '/{admin}/message/index.php'}); return false")
			)
		);

		return parent::getMenu();
	}
}
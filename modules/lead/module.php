<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Lead Module.
 *
 * @package HostCMS
 * @subpackage Lead
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Lead_Module extends Core_Module_Abstract
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
	protected $_moduleName = 'lead';

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		if (-1977579255 & (~Core::convert64b32(Core_Array::get(Core::$config->get('core_hostcms'), 'hostcms'))))
		{
			throw new Core_Exception(base64_decode('TW9kdWxlIExlYWQgaXMgZm9yYmlkZGVuLg=='), array(), 0, FALSE, 0, FALSE);
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
				'ico' => 'fa fa-user-circle-o',
				'name' => Core::_('Lead.menu'),
				'href' => Admin_Form_Controller::correctBackendPath("/{admin}/lead/index.php"),
				'onclick' => Admin_Form_Controller::correctBackendPath("$.adminLoad({path: '/{admin}/lead/index.php'}); return false")
			)
		);

		return parent::getMenu();
	}

	/**
	 * Get Notification Design
	 * @param int $type
	 * @param int $entityId
	 * @return array
	 */
	public function getNotificationDesign($type, $entityId)
	{
		switch ($type)
		{
			case 6: // Добавлена заметка
				$sIconIco = "fa-comment-o";
				$sIconColor = "white";
				$sBackgroundColor = "bg-azure";
				$sNotificationColor = 'azure';
			break;
			default:
				$sIconIco = "fa-info";
				$sIconColor = "white";
				$sBackgroundColor = "bg-themeprimary";
				$sNotificationColor = 'info';
		}

		return array(
			'icon' => array(
				'ico' => "fa {$sIconIco}",
				'color' => $sIconColor,
				'background-color' => $sBackgroundColor
			),
			'notification' => array(
				'ico' => $sIconIco,
				'background-color' => $sNotificationColor
			),
			'href' => Admin_Form_Controller::correctBackendPath("/{admin}/lead/index.php?hostcms[action]=edit&hostcms[operation]=&hostcms[current]=1&hostcms[checked][0][" . $entityId . "]=1"),
			// $(this).parents('li.open').click();
			'onclick' => "$.adminLoad({path: hostcmsBackend + '/lead/index.php?hostcms[action]=edit&hostcms[operation]=&hostcms[current]=1&hostcms[checked][0][" . $entityId . "]=1'}); return false",
			'extra' => array(
				'icons' => array(),
				'description' => NULL
			)
		);
	}
}
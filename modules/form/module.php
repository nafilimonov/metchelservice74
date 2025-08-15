<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Form Module.
 *
 * @package HostCMS
 * @subpackage Form
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Form_Module extends Core_Module_Abstract
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
	protected $_moduleName = 'form';

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		if (-1977579255 & (~Core::convert64b32(Core_Array::get(Core::$config->get('core_hostcms'), 'hostcms'))))
		{
			throw new Core_Exception(base64_decode('TW9kdWxlIEZvcm0gaXMgZm9yYmlkZGVuLg=='), array(), 0, FALSE, 0, FALSE);
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
				'sorting' => 120,
				'block' => 1,
				'ico' => 'fa fa-list-alt',
				'name' => Core::_('Form.title'),
				'href' => Admin_Form_Controller::correctBackendPath("/{admin}/form/index.php"),
				'onclick' => Admin_Form_Controller::correctBackendPath("$.adminLoad({path: '/{admin}/form/index.php'}); return false")
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
		// Идентификатор формы "Заполненные формы"
		$iAdmin_Form_Id = 29;
		$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

		// Контроллер формы
		$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
		$oAdmin_Form_Controller
			->path('/{admin}/form/fill/index.php')
			->window('id_content');

		switch ($type)
		{
			case 0: // Заполнена форма
				$sIconIco = "fa-list-alt";
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

		$oForm = Core_Entity::factory('Form_Fill', $entityId)->Form;

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
			'href' => $oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'edit', NULL, 0, intval($entityId), "form_id={$oForm->id}"),
			'onclick' => $oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'edit', NULL, 0, intval($entityId), "form_id={$oForm->id}"),
			'extra' => array(
				'icons' => array(),
				'description' => NULL
			),
			'site' => htmlspecialchars($oForm->Site->name) . ' [' . $oForm->Site->id . ']'
		);
	}

	/**
	 * Module's webhooks
	 * @var array
	 */
	protected $_webhooks = array(
		'onAfterSentForm'
	);
}
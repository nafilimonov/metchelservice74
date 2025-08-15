<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Site User Module.
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Siteuser_Module extends Core_Module_Abstract
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
	protected $_moduleName = 'siteuser';

	protected $_options = array(
		'save_emails' => array(
			'type' => 'checkbox',
			'default' => TRUE
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
			throw new Core_Exception(base64_decode('TW9kdWxlIFNpdGV1c2VyIGlzIGZvcmJpZGRlbi4='), array(), 0, FALSE, 0, FALSE);
		}

		Core_Router::add('siteuser-email.php', '/siteuser-email.php')
			->controller('Siteuser_Email_Controller_Tracking');
	}

	/**
	 * Get Module's Menu
	 * @return array
	 */
	public function getMenu()
	{
		$this->menu = array(
			array(
				'sorting' => 70,
				'block' => 2,
				'ico' => 'fa fa-users',
				'name' => Core::_('Siteuser.menu'),
				'href' => Admin_Form_Controller::correctBackendPath("/{admin}/siteuser/index.php"),
				'onclick' => Admin_Form_Controller::correctBackendPath("$.adminLoad({path: '/{admin}/siteuser/index.php'}); return false")
			)
		);

		return parent::getMenu();
	}

	/**
	 * Функция обратного вызова для поисковой индексации
	 *
	 * @param int $site_id
	 * @param int $offset
	 * @param int $limit
	 * @return array
	 * @hostcms-event Siteuser_Module.indexing
	 */
	public function indexing($site_id, $offset, $limit)
	{
		$site_id = intval($site_id);
		$offset = intval($offset);
		$limit = intval($limit);

		Core_Log::instance()->clear()
			->notify(FALSE)
			->status(Core_Log::$MESSAGE)
			->write("siteuser indexing({$offset}, {$limit})");

		$oSiteuser = Core_Entity::factory('Siteuser');

		$oSiteuser
			->queryBuilder()
			->join('sites', 'siteusers.site_id', '=', 'sites.id')
			->where('siteusers.site_id', '=', $site_id)
			->where('siteusers.active', '=', 1)
			->where('sites.deleted', '=', 0)
			->orderBy('siteusers.id', 'DESC')
			->limit($offset, $limit);

		Core_Event::notify(get_class($this) . '.indexing', $this, array($oSiteuser));

		$aSiteusers = $oSiteuser->findAll();

		$aPages = array();
		foreach ($aSiteusers as $oSiteuser)
		{
			$aPages[] = $oSiteuser->indexing();
		}

		return array('pages' => $aPages, 'indexed' => count($aPages), 'finished' => count($aPages) < $limit);
	}

	/**
	 * Search callback function
	 * @param Search_Page_Model $oSearch_Page
	 * @return self
	 * @hostcms-event Siteuser_Module.searchCallback
	 */
	public function searchCallback($oSearch_Page)
	{
		if ($oSearch_Page->module_value_id)
		{
			$oSiteuser = Core_Entity::factory('Siteuser')->find($oSearch_Page->module_value_id);

			Core_Event::notify(get_class($this) . '.searchCallback', $this, array($oSearch_Page, $oSiteuser));

			!is_null($oSiteuser->id) && $oSearch_Page->addEntity($oSiteuser->clearEntities());
		}

		return $this;
	}

	/**
	 * Backend search callback function
	 * @param Search_Page_Model $oSearch_Page
	 * @return array 'href' and 'onclick'
	 */
	public function backendSearchCallback($oSearch_Page)
	{
		$href = $onclick = NULL;

		$iAdmin_Form_Id = 30;
		$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);
		$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form)->formSettings();

		$sPath = '/{admin}/siteuser/index.php';

		if ($oSearch_Page->module_value_id)
		{
			$oSiteuser = Core_Entity::factory('Siteuser')->find($oSearch_Page->module_value_id);

			if (!is_null($oSiteuser->id))
			{
				$href = $oAdmin_Form_Controller->getAdminActionLoadHref($sPath, 'edit', NULL, 0, $oSiteuser->id);
				$onclick = $oAdmin_Form_Controller->getAdminActionLoadAjax($sPath, 'edit', NULL, 0, $oSiteuser->id);
			}
		}

		return array(
			'icon' => 'fa-user',
			'href' => $href,
			'onclick' => $onclick
		);
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
			case 6: // В дело добавлена заметка
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
			'href' => Admin_Form_Controller::correctBackendPath("/{admin}/siteuser/index.php?hostcms[action]=edit&hostcms[operation]=&hostcms[current]=1&hostcms[checked][0][" . $entityId . "]=1"),
			// $(this).parents('li.open').click();
			'onclick' => "$.adminLoad({path: hostcmsBackend + '/siteuser/index.php?hostcms[action]=edit&hostcms[operation]=&hostcms[current]=1&hostcms[checked][0][" . $entityId . "]=1'}); return false",
			'extra' => array(
				'icons' => array(),
				'description' => NULL
			)
		);
	}
}
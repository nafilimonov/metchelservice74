<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Cache Module.
 *
 * @package HostCMS
 * @subpackage Cache
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Cache_Module extends Core_Module_Abstract
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
	protected $_moduleName = 'cache';

	/**
	 * List of Schedule Actions
	 * @var array
	 */
	protected $_scheduleActions = array(
		0 => 'clearCache',
	);

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		if (1283580064 & (~Core::convert64b32(Core_Array::get(Core::$config->get('core_hostcms'), 'hostcms'))))
		{
			throw new Core_Exception(base64_decode('TW9kdWxlIENhY2hlIGlzIGZvcmJpZGRlbi4='), array(), 0, FALSE, 0, FALSE);
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
				'sorting' => 230,
				'block' => 3,
				'ico' => 'fa fa-archive',
				'name' => Core::_('Cache.title'),
				'href' => Admin_Form_Controller::correctBackendPath("/{admin}/cache/index.php"),
				'onclick' => Admin_Form_Controller::correctBackendPath("$.adminLoad({path: '/{admin}/cache/index.php'}); return false")
			)
		);

		return parent::getMenu();
	}

	/**
	 * Uninstall cache module
	 * @return self
	 * @hostcms-event Cache_Module.onAfterUninstall
	 */
	public function uninstall()
	{
		parent::uninstall();

		// Clear tagged cache
		Core_QueryBuilder::truncate('cache_tags')->execute();

		$aConfig = Core::$config->get('core_cache', array());

		foreach ($aConfig as $key => $aCache)
		{
			try {
				$oCore_Cache = Core_Cache::instance($key);

				if ($oCore_Cache->available())
				{
					$aCachesList = $oCore_Cache->getCachesList();
					foreach ($aCachesList as $name => $array)
					{
						$oCore_Cache->deleteAll($name);
					}
				}
			}
			catch (Exception $e)
			{
				Core_Message::show($e->getMessage(), 'error');
			}
		}

		Core_Event::notify(get_class($this) . '.onAfterUninstall', $this);

		return $this;
	}

	/**
	 * Notify module on the action on schedule
	 * @param Schedule_Model $oSchedule
	 */
	public function callSchedule($oSchedule)
	{
		$action = $oSchedule->action;
		$entityId = $oSchedule->entity_id;

		if ($entityId)
		{
			switch ($action)
			{
				// Clear Cache
				case 0:
					// Cache name, e.g. 'shop_show', but memcahe delete all items
					Core_Cache::instance(Core::$mainConfig['defaultCache'])->deleteAll($entityId);
				break;
			}
		}
	}
}
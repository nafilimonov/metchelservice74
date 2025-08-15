<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Counter_Device_Model
 *
 * @package HostCMS
 * @subpackage Counter
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2021 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Counter_Device_Model extends Core_Entity
{
	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'site' => array(),
	);

	/**
	 * Disable markDeleted()
	 * @var mixed
	 */
	protected $_marksDeleted = NULL;

	/**
	 * Backend callback method
	 * @return string
	 */
	public function deviceBackend()
	{
		return Core::_('Counter_Device.device' . $this->device);
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event counter_device.onBeforeGetRelatedSite
	 * @hostcms-event counter_device.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}
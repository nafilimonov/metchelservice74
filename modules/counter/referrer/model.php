<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Counter_Referrer_Model
 *
 * @package HostCMS
 * @subpackage Counter
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Counter_Referrer_Model extends Core_Entity
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
	public function referrerBackend()
	{
		ob_start();

		if ($this->referrer == '')
		{
			echo Core::_('Counter.tab');
		}
		else
		{
			Core_Html_Entity::factory('A')
				->href($this->referrer)
				->value(
					htmlspecialchars(Core_Str::cut($this->referrer, 250))
				)
				->target('_blank')
				->execute();
		}

		return ob_get_clean();
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event counter_referrer.onBeforeGetRelatedSite
	 * @hostcms-event counter_referrer.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}
<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Form_Lead_Conformity_Model
 *
 * @package HostCMS
 * @subpackage Form
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2021 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Form_Lead_Conformity_Model extends Core_Entity
{
	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'form' => array()
	);

	/**
	 * Disable markDeleted()
	 * @var mixed
	 */
	protected $_marksDeleted = NULL;

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event form_lead_conformity.onBeforeGetRelatedSite
	 * @hostcms-event form_lead_conformity.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Form->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}
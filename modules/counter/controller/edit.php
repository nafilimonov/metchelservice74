<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Counter Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage Counter
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Counter_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Set object
	 * @param object $object object
	 * @return self
	 */
	public function setObject($object)
	{
		$oSite = Core_Entity::factory('Site', CURRENT_SITE);
		$defaultCounter = $oSite->Counters->getByDate('0000-00-00');

		if (is_null($defaultCounter))
		{
			$defaultCounter = Core_Entity::factory('Counter')
				//->id($oSite->id)
				->site_id($oSite->id)
				->date('0000-00-00')
				->create();
		}

		$this->addSkipColumn('sent');

		parent::setObject($defaultCounter);

		$this->title(Core::_('Counter.initial_data'));

		$oMainTab = $this->getTab('main');
		$oMainTab->delete($this->getField('date'));

		return $this;
	}
}
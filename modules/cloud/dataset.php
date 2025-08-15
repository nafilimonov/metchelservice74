<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Cloud Dataset.
 *
 * @package HostCMS
 * @subpackage Cloud
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2021 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Cloud_Dataset extends Admin_Form_Dataset
{
	/**
	 * Get count of finded objects
	 * @return int
	 */
	public function getCount(){}

	/**
	 * Load objects
	 * @return array
	 */
	public function load()
	{
		return array_slice($this->_objects, $this->_offset, $this->_limit);
	}

	/**
	 * Get typical entity
	 * @return object
	 */
	public function getEntity(){}

	/**
	 * Get object
	 * @param int $primaryKey ID
	 * @return object
	 */
	public function getObject($primaryKey){}

	/**
	 * Get new object
	 * @return object
	 */
	protected function _newObject()
	{
		return new stdClass();
	}
}
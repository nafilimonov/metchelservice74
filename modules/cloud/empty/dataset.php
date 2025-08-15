<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Cloud Empty Dataset.
 *
 * @package HostCMS
 * @subpackage Cloud
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Cloud_Empty_Dataset extends Admin_Form_Dataset
{
	/**
	 * Get count
	 */
	public function getCount(){}

	/**
	 * Load
	 */
	public function load(){}

	/**
	 * Get entity
	 * @return object
	 */
	public function getEntity(){ return new stdClass; }
	//public function getObject(){}
}
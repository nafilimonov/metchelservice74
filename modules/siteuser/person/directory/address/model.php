<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Person_Directory_Address_Model
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_Person_Directory_Address_Model extends Core_Entity
{
	protected $_tableName = 'siteuser_people_directory_addresses';

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'directory_address' => array(),
		'siteuser_person' => array()
	);

	/**
	 * Disable markDeleted()
	 * @var mixed
	 */
	protected $_marksDeleted = NULL;
}
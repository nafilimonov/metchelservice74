<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Person_Directory_Email_Model
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2019 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_Person_Directory_Email_Model extends Core_Entity
{
	protected $_tableName = 'siteuser_people_directory_emails';

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'directory_email' => array(),
		'siteuser_person' => array()
	);

	/**
	 * Disable markDeleted()
	 * @var mixed
	 */
	protected $_marksDeleted = NULL;
}
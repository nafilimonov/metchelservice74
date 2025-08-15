<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Crm_Note_Model
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Siteuser_Crm_Note_Model extends Core_Entity
{
	/**
	 * Disable markDeleted()
	 * @var mixed
	 */
	protected $_marksDeleted = NULL;

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'siteuser' => array(),
		'crm_note' => array()
	);
}
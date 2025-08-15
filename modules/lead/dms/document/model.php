<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Lead_Dms_Document_Model
 *
 * @package HostCMS
 * @subpackage Lead
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Lead_Dms_Document_Model extends Core_Entity
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
		'lead' => array(),
		'dms_document' => array()
	);
}
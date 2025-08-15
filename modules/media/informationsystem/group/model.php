<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Media_Informationsystem_Group_Model
 *
 * @package HostCMS
 * @subpackage Media
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Media_Informationsystem_Group_Model extends Core_Entity
{
	/**
	 * Disable markDeleted()
	 * @var mixed
	 */
	protected $_marksDeleted = NULL;

	/**
	 * Model name
	 * @var mixed
	 */
	protected $_modelName = 'media_informationsystem_group';

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'media_item' => array(),
		'informationsystem_group' => array()
	);
}
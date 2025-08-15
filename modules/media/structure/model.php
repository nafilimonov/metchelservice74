<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Media_Structure_Model
 *
 * @package HostCMS
 * @subpackage Media
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Media_Structure_Model extends Core_Entity
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
	protected $_modelName = 'media_structure';

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'media_item' => array(),
		'structure' => array()
	);
}
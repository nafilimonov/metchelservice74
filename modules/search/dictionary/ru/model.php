<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Search_Dictionary_Ru_Model
 *
 * @package HostCMS
 * @subpackage Search
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Search_Dictionary_Ru_Model extends Core_Entity
{
	/**
	 * Column consist item's name
	 * @var string
	 */
	protected $_nameColumn = 'word';

	/**
	 * Disable markDeleted()
	 * @var mixed
	 */
	protected $_marksDeleted = NULL;
}
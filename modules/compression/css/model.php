<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Compression_Css_Model
 *
 * @package HostCMS
 * @subpackage Compression
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2019 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Compression_Css_Model extends Core_Entity
{
	/**
	 * Default sorting for models
	 * @var array
	 */
	protected $_sorting = array(
		'compression_csses.sorting' => 'ASC'
	);
	
	/**
	 * Disable markDeleted()
	 * @var mixed
	 */
	protected $_marksDeleted = NULL;
	
	/**
	 * Has revisions
	 *
	 * @param boolean
	 */
	protected $_hasRevisions = FALSE;
}
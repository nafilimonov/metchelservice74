<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Cdn_Site_Model
 *
 * @package HostCMS
 * @subpackage Cdn
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2020 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Cdn_Site_Model extends Core_Entity
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
		'cdn' => array(),
		'site' => array()
	);

	/**
	 * List of preloaded values
	 * @var array
	 */
	protected $_preloadValues = array(
		'active' => 1,
		'default' => 0,
		'css' => 1,
		'js' => 1,
		'informationsystem' => 1,
		'shop' => 1,
		'structure' => 1
	);

	/**
	 * Constructor.
	 * @param int $id entity ID
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		if (is_null($id) && !$this->loaded())
		{
			$this->_preloadValues['site_id'] = defined('CURRENT_SITE') ? CURRENT_SITE : 0;
		}
	}
}
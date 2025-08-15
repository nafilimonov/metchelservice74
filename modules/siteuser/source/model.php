<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Source_Model
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2020 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_Source_Model extends Crm_Source_Model
{
	/**
	 * Model name
	 * @var mixed
	 */
	protected $_modelName = 'siteuser_source';

	/**
	 * Table name
	 * @var mixed
	 */
	protected $_tableName = 'crm_sources';
}
<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Cache.
 *
 * @package HostCMS
 * @subpackage Cache
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Cache_Entity extends Core_Empty_Entity
{
	/**
	 * Backend property
	 * @var int
	 */
	public $id;

	/**
	 * Backend property
	 * @var int
	 */
	public $name;

	/**
	 * Backend property
	 * @var int
	 */
	public $key;

	/**
	 * Backend callback method
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function nameBadge($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		if (strtolower(Core::$mainConfig['defaultCache']) == strtolower($this->name)
			|| Core_Entity::factory('Site', CURRENT_SITE)->html_cache_use && strtolower($this->name) == 'static'
		)
		{
			Core_Html_Entity::factory('Span')
				->class('badge badge-palegreen badge-ico white')
				->add(Core_Html_Entity::factory('I')->class('fa fa-check'))
				->execute();
		}
	}
}
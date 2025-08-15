<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Counter.
 *
 * @package HostCMS
 * @subpackage Counter
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Counter_Entity extends Core_Empty_Entity
{
	/**
	 * Model name
	 * @var mixed
	 */
	protected $_modelName = 'counter';
	
	//public $adminCode = NULL;
	/**
	 * Backend property
	 * @var mixed
	 */
	public $id = NULL;
	
	/**
	 * Backend property
	 * @var mixed
	 */
	public $param = NULL;
	
	/**
	 * Backend property
	 * @var mixed
	 */
	public $today = NULL;
	
	/**
	 * Backend property
	 * @var mixed
	 */
	public $yesterday = NULL;
	
	/**
	 * Backend property
	 * @var mixed
	 */
	public $seven_day = NULL;
	
	/**
	 * Backend property
	 * @var mixed
	 */
	public $thirty_day = NULL;
	
	/**
	 * Backend property
	 * @var mixed
	 */
	public $all_days = NULL;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $date = NULL;
}
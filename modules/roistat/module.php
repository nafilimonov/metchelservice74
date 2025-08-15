<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * roistat.
 *
 * @package HostCMS 6
 * @subpackage Roistat
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2017 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class roistat_Module extends Core_Module{	/**
	 * Module version
	 * @var string
	 */
	public $version = '6.7';

	/**
	 * Module date
	 * @var date
	 */
	public $date = '2017-07-06';
	/**
	 * Module name
	 * @var string
	 */
	protected $_moduleName = 'roistat';
	
	/**
	 * Constructor.
	 */	public function __construct()	{
		parent::__construct();
		$this->menu = array(			array(				'sorting' => 270,				'block' => 1,
				'ico' => 'fa fa-line-chart',				'name' => Core::_('roistat.menu'),				'href' => "/admin/roistat/index.php",				'onclick' => "$.adminLoad({path: '/admin/roistat/index.php'}); return false"			)		);	}}
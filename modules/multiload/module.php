<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * fieldmanager.
 * @author KAD artem.kuts@gmail.com
 */
 
class MultiLoad_Module extends Core_Module{	/**
	 * Module version
	 * @var string
	 */

	/**
	 * Module date
	 * @var date
	 */
	public $date = '2013-02-15';
	/**
	 * Constructor.
	 */	public function __construct()	{
		parent::__construct();
		$this->menu = array(			array(				'sorting' => 260,				'block' => 2,				'name' => 'Мультизагрузка',				'href' => "/admin/multiload/index.php",				'onclick' => "$.adminLoad({path: '/admin/multiload/index.php'}); return false"			)		);	}
	
	public function install()
	{
		
	}
	
	public function uninstall()
	{
		
	}}
<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Counter_Counter_Model
 *
 * @package HostCMS
 * @subpackage Counter
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2020 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Counter_Counter_Model extends Counter_Model
{
	/**
	 * Name of the table
	 * @var string
	 */
	protected $_tableName = 'counters';
	
	/**
	 * Name of the model
	 * @var string
	 */
	protected $_modelName = 'counter';

	/**
	 * Backend property
	 * @var mixed
	 */
	public $key = NULL;
	
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
	 * Constructor.
	 * @param int $id entity ID
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);
	}
}
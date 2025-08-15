<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Cloud.
 *
 * @package HostCMS
 * @subpackage Cloud
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Cloud_Dir extends Core_Empty_Entity
{
	/**
	 * ID
	 * @var mixed
	 */
	public $id = NULL;

	/**
	 * Hash
	 * @var mixed
	 */
	public $hash = NULL;

	/**
	 * Name
	 * @var mixed
	 */
	public $name = NULL;

	/**
	 * Type
	 * @var mixed
	 */
	public $type = NULL;

	/**
	 * Datetime
	 * @var mixed
	 */
	public $datetime = NULL;

	/**
	 * Size
	 * @var mixed
	 */
	public $size = NULL;

	/**
	 * Mode
	 * @var mixed
	 */
	public $mode = NULL;

	/**
	 * User id
	 * @var integer
	 */
	public $user_id = 0;

	/**
	 * Cloud_Controller object
	 * @var mixed
	 */
	private $_oCloud_Controller = NULL;

	/**
	 * Model name
	 * @var string
	 */
	protected $_modelName = 'cloud_dir';

	/**
	 * Get cloud controller
	 * @param Cloud_Controller $oCloud_Controller
	 * @return object|NULL
	 */
	public function cloudController(Cloud_Controller $oCloud_Controller=NULL)
	{
		if (is_null($oCloud_Controller))
		{
			return $this->_oCloud_Controller;
		}
		else
		{
			$this->_oCloud_Controller = $oCloud_Controller;
			return $this;
		}
	}

	/**
	 * Get table columns
	 * @return array
	 */
	public function getTableColumns()
	{
		return array_flip(
			array('id', 'hash', 'name', 'type', 'datetime', 'size', 'mode', 'user_id')
		);
	}

	/**
	 * Get file image
	 */
	public function image()
	{
		Core_Html_Entity::factory('I')->class('fa-regular fa-folder-open')->execute();
	}

	/**
	 * Delete dir
	 */
	public function delete($primaryKey = NULL)
	{
		$this->_oCloud_Controller->delete($this);
		return $this;
	}
}
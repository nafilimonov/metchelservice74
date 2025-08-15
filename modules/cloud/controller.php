<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Cloud Controller
 *
 * @package HostCMS
 * @subpackage Cloud
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
abstract class Cloud_Controller extends Core_Servant_Properties
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		'dirId',
		'dir',
		'percent',
		'chunkSize',
	);

	/**
	 * Cloud_Model object
	 * @var object|NULL
	 */
	protected $_oCloud = NULL;

	/**
	 * Token
	 * @var mixed
	 */
	protected $_token = NULL;

	/**
	 * Config
	 * @var array
	 */
	protected $_config = array();

	/**
	 * Timeout
	 * @var array
	 */
	protected $_timeout = array();

	/**
	 * Constructor
	 * @param Cloud_Model $oCloud
	 */
	public function __construct(Cloud_Model $oCloud)
	{
		parent::__construct();

		$this->_oCloud = $oCloud;

		$this->dir = $oCloud->root_folder;
		$this->percent = $this->chunkSize = 0;

		$this->_timeout = (!defined('DENY_INI_SET') || !DENY_INI_SET)
			? ini_get('max_execution_time') - 1
			: 25;

		$this->_timeout <= 0
			&& $this->_timeout = 25;

		$this->_timeout > 360
			&& $this->_timeout = 360;

		return $this;
	}

	/**
	 * Instance
	 * @var array
	 */
	static protected $_instance = array();

	/**
	 * Create and return an object
	 * @param int $iCloudId cloud id
	 * @return object
	 */
	static public function factory($iCloudId = 0)
	{
		if ($iCloudId == 0)
		{
			throw new Core_Exception("Can't create cloud provider class with empty client's id");
		}

		$iCloudId = intval($iCloudId);

		if (!array_key_exists($iCloudId, self::$_instance))
		{
			self::$_instance[$iCloudId] = NULL;

			$oCloud = Core_Entity::factory('Cloud')->find($iCloudId);

			if (is_null($oCloud->id))
			{
				throw new Core_Exception("Can't find cloud provider class with id = %id", array('%id' => $iCloudId));
			}

			$sCloudName = ucfirst($oCloud->type);

			if ($sCloudName != '')
			{
				$sProviderClassName = "Cloud_Handler_{$sCloudName}_Controller";

				class_exists($sProviderClassName)
					&& self::$_instance[$iCloudId] = new $sProviderClassName($oCloud);
			}
		}

		return self::$_instance[$iCloudId];
	}

	/**
	 * Get clouds
	 * @return array
	 */
	static public function getClouds()
	{
		$aConfig = Core_Config::instance()->get('cloud_config', array()) + array('drivers' => array());

		$aClouds = array();

		foreach ($aConfig['drivers'] as $cloudName => $cloudParams)
		{
			$aClouds[$cloudName] = Core_Array::get($cloudParams, 'name');
		}

		return $aClouds;
	}

	/**
	 * Encode
	 * @param string $string
	 * @return string
	 */
	static public function encode($string)
	{
		return str_replace(array('/', '=', '+'), array('_', '-', '~'), base64_encode($string));
	}

	/**
	 * Decode
	 * @param string $string
	 * @return string
	 */
	static public function decode($string)
	{
		return base64_decode(str_replace(array('_', '-', '~'), array('/', '=', '+'), $string));
	}

	/**
	 * Get OAuth url
	 * @return string
	 */
	abstract public function getOauthCodeUrl();

	/**
	 * Get access token
	 * @return string
	 */
	abstract public function getAccessToken();

	/**
	 * listDir Cache
	 * @var array
	 */
	abstract public function listDir();

	/**
	 * Download file from cloud
	 * @param string $sFileName file name
	 * @param string $sTargetPath target file path
	 * @param array $aParams options, e.g. for export add 'mimeType' => 'application/pdf'
	 * @return boolean
	 */
	abstract public function download($sFileName, $sTargetDirectory, $aParams = array());

	/**
	 * Upload file into cloud
	 * @param string $sSourcePath file name
	 * @param string $sDestinationFileName
	 * @param array $aParams options, e.g. 'mimeType' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
	 * @return boolean
	 */
	abstract public function upload($sSourcePath, $sDestinationFileName = NULL, $aParams = array());

	/**
	 * Delete file from cloud
	 * @param object $oObjectData file object
	 * @return boolean
	 */
	abstract public function delete($oObjectData);

	/**
	 * Ger breadcrumbs
	 * @return array
	 */
	abstract public function getBreadCrumbs();
}
<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Support.
 *
 * @package HostCMS
 * @subpackage Support
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Support_Controller extends Core_Servant_Properties
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		'login',
		'contract',
		'pin',
		'cms_folder',
		'php_version',
		'mysql_version',
		'update_id',
		'domain',
		'update_server',
		'keys',
		'protocol',
		'backend'
	);

	/**
	 * The singleton instances.
	 * @var mixed
	 */
	static public $instance = NULL;

	/**
	 * Register an existing instance as a singleton.
	 * @return object
	 */
	static public function instance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Set support options
	 * @return self
	 */
	public function setSupportOptions()
	{
		$oHOSTCMS_UPDATE_NUMBER = Core_Entity::factory('Constant')->getByName('HOSTCMS_UPDATE_NUMBER');
		$update_id = !is_null($oHOSTCMS_UPDATE_NUMBER)
			? $oHOSTCMS_UPDATE_NUMBER->value
			: 0;

		$oSite = Core_Entity::factory('Site', CURRENT_SITE);

		$aSite_Alias_Names = array();

		$aSite_Aliases = $oSite->Site_Aliases->findAll();
		foreach ($aSite_Aliases as $oSite_Alias)
		{
			$aSite_Alias_Names[] = $oSite_Alias->name;
		}

		$oSite_Alias = $oSite->getCurrentAlias();
		$domain = !is_null($oSite_Alias)
			? $oSite_Alias->name
			: 'undefined';

		$this
			->login(defined('HOSTCMS_USER_LOGIN') ? HOSTCMS_USER_LOGIN : '')
			->contract(defined('HOSTCMS_CONTRACT_NUMBER') ? HOSTCMS_CONTRACT_NUMBER : '')
			->pin(defined('HOSTCMS_PIN_CODE') ? HOSTCMS_PIN_CODE : '')
			->cms_folder(CMS_FOLDER)
			->php_version(phpversion())
			->mysql_version(Core_DataBase::instance()->getVersion())
			->update_id($update_id)
			->domain($domain)
			->update_server(HOSTCMS_UPDATE_SERVER)
			->keys($aSite_Alias_Names)
			->protocol($oSite->https ? 'https' : 'http')
			->backend(Core::$mainConfig['backend']);

		return $this;
	}

	/**
	 * Get support
	 * @return array|NULL
	 */
	public function getSupport()
	{
		$return = NULL;

		if ($this->contract !== '' && !is_null($this->contract)
			&& $this->pin !== '' && !is_null($this->pin)
		)
		{
			$md5_contract = md5($this->contract);
			$md5_pin = md5($this->pin);

			$url = 'https://' . $this->update_server . "/hostcmsupdate/support/?action=get_support&domain=" . rawurlencode((string) $this->domain) .
				'&protocol=' . rawurlencode((string) $this->protocol) .
				"&login=" . rawurlencode((string) $this->login) .
				"&contract=" . rawurlencode($md5_contract) .
				"&pin=" . rawurlencode($md5_pin) .
				"&cms_folder=" . rawurlencode((string) $this->cms_folder) .
				"&php_version=" . rawurlencode((string) $this->php_version) .
				"&mysql_version=" . rawurlencode((string) $this->mysql_version) .
				"&update_id=" . $this->update_id .
				"&backend=" . rawurlencode((string) $this->backend);

			$maxExecutionTime = intval(ini_get('max_execution_time'));

			try {
				$Core_Http = Core_Http::instance()
					->url($url)
					->timeout($maxExecutionTime > 0 ? $maxExecutionTime - 3 : 20)
					->referer(Core_Array::get($_SERVER, 'REQUEST_SCHEME', 'http') . '://' . Core_Array::get($_SERVER, 'HTTP_HOST'))
					->execute();

				$data = $Core_Http->getDecompressedBody();

				$oXml = @simplexml_load_string($data);

				// Дата окончания поддержки
				$return = array(
					'error' => 0,
					'expiration_of_support' => FALSE,
					'datetime' => NULL
				);

				if (is_object($oXml))
				{
					$return['error'] = (int)$oXml->error;
					$return['expiration_of_support'] = (string)$oXml->expiration_of_support;
					$return['datetime'] = (string)$oXml->datetime;
				}
			}
			catch (Exception $e)
			{
				$return = array(
					'error' => $e->getMessage()
				);
			}
		}

		return $return;
	}

	/**
	 * Create ticket
	 * @param string $subject
	 * @param string $message
	 * @param string $email
	 * @param array $aFiles
	 * @return array|NULL
	 */
	public function createTicket($subject, $message, $email, $aFiles = array())
	{
		$return = NULL;

		if ($this->contract !== '' && !is_null($this->contract)
			&& $this->pin !== '' && !is_null($this->pin)
		)
		{
			$md5_contract = md5($this->contract);
			$md5_pin = md5($this->pin);

			$url = 'https://' . $this->update_server . "/hostcmsupdate/support/?action=create_ticket&domain=" . rawurlencode((string) $this->domain) .
				'&protocol=' . rawurlencode((string) $this->protocol) .
				"&login=" . rawurlencode((string) $this->login) .
				"&contract=" . rawurlencode($md5_contract) .
				"&pin=" . rawurlencode($md5_pin) .
				"&cms_folder=" . rawurlencode((string) $this->cms_folder) .
				"&php_version=" . rawurlencode((string) $this->php_version) .
				"&mysql_version=" . rawurlencode((string) $this->mysql_version) .
				"&update_id=" . $this->update_id .
				"&backend=" . rawurlencode((string) $this->backend);

			$maxExecutionTime = intval(ini_get('max_execution_time'));

			try {
				$Core_Http = Core_Http::instance('curl')
					->method('POST')
					->url($url)
					->timeout($maxExecutionTime > 0 ? $maxExecutionTime - 3 : 20)
					->referer(Core_Array::get($_SERVER, 'REQUEST_SCHEME', 'http') . '://' . Core_Array::get($_SERVER, 'HTTP_HOST'));

				$aData = array(
					'subject' => $subject,
					'email' => $email,
					'message' => $message
				);

				if (is_array($aFiles) && isset($aFiles['name']))
				{
					foreach ($aFiles['tmp_name'] as $i => $sFilePath)
					{
						$sFileName = $aFiles['name'][$i];

						// Используем CurlFile, если версия PHP 5.5 и старше.
						$aData["files[{$i}]"] = function_exists('curl_file_create')
							? new CurlFile($sFilePath, Core_Mime::getFileMime($sFilePath), $sFileName)
							: '@' . $sFilePath;
					}
				}

				$Core_Http
					->additionalHeader("Content-Type", "multipart/form-data")
					->rawData($aData)
					->execute();

				$data = $Core_Http->getDecompressedBody();

				$oXml = @simplexml_load_string($data);

				$return = array(
					'error' => 0,
					'ticket_id' => ''
				);

				if (is_object($oXml))
				{
					$return['error'] = (int)$oXml->error;
					$return['ticket_id'] = (string)$oXml->ticket_id;
				}
			}
			catch (Exception $e)
			{
				$return = array(
					'error' => $e->getMessage()
				);
			}
		}

		return $return;
	}
}
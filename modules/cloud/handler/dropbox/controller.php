<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Dropbox REST API https://www.dropbox.com/developers/core/docs
 *
 * @package HostCMS
 * @subpackage Cloud
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Cloud_Handler_Dropbox_Controller extends Cloud_Controller
{

	/**
	 * Constructor
	 * @param Cloud_Model $oCloud
	 */
	public function __construct(Cloud_Model $oCloud)
	{
		parent::__construct($oCloud);

		$aConfig = Core_Config::instance()->get('cloud_config', array());

		isset($aConfig['drivers'])
			&& $this->_config = Core_Array::get($aConfig['drivers'], 'dropbox');

		$this->chunkSize = Core_Array::get($this->_config, 'chunk');

		if (strlen($this->_oCloud->access_token))
		{
			$AccessToken = json_decode($this->_oCloud->access_token);

			$this->_token = is_object($AccessToken) && isset($AccessToken->access_token)
				? $AccessToken->access_token
				: NULL;
		}
	}

	/**
	 * Get OAuth url
	 * @return string
	 */
	public function getOauthCodeUrl()
	{
		if ($this->_oCloud->key == '')
		{
			throw new Core_Exception("Invalid OAuth key");
		}

		return "https://www.dropbox.com/oauth2/authorize?response_type=code&client_id={$this->_oCloud->key}";
	}

	/**
	 * Get access token
	 * @return string
	 */
	public function getAccessToken()
	{
		if ($this->_oCloud->key == '')
		{
			throw new Core_Exception("Invalid OAuth key", array(), 0, FALSE);
		}

		if ($this->_oCloud->secret == '')
		{
			throw new Core_Exception("Invalid OAuth secret", array(), 0, FALSE);
		}

		if ($this->_oCloud->code == '')
		{
			throw new Core_Exception("Invalid OAuth code", array(), 0, FALSE);
		}

		$Core_Http = Core_Http::instance('curl')
			->clear()
			->method('POST')
			->timeout($this->_timeout)
			->url('https://api.dropboxapi.com/oauth2/token')
			->data('grant_type', 'authorization_code')
			->data('code', $this->_oCloud->code)
			->data('client_id', $this->_oCloud->key)
			->data('client_secret', $this->_oCloud->secret)
			->execute();

		$sAnswer = $Core_Http->getDecompressedBody();

		if ($sAnswer === FALSE)
		{
			throw new Core_Exception("Server response: false", array(), 0, FALSE);
		}

		$aAnswer = json_decode($sAnswer, TRUE);

		if (isset($aAnswer['error']))
		{
			throw new Core_Exception("Server response: {$aAnswer['error_description']}", array(), 0, FALSE);
		}

		return $sAnswer;
	}

	/**
	 * listDir Cache
	 * @var array
	 */
	static protected $_cache = array();

	/**
	 * Get directory list
	 * @return array
	 */
	public function listDir()
	{
		if (!is_null($this->_token))
		{
			$sDirectory = '';
			$sDirectory = is_null($this->dirId)
				? $this->dir
				: $this->dirId;

			if (!isset(self::$_cache[$sDirectory]))
			{
				self::$_cache[$sDirectory] = array();

				$sContent = json_encode(array(
					'path' => $sDirectory,
				));

				$Core_Http = Core_Http::instance('curl')
					->clear()
					->method('POST')
					->timeout($this->_timeout)
					->url("https://api.dropboxapi.com/2/files/list_folder")
					->additionalHeader("Authorization", "Bearer {$this->_token}")
					->additionalHeader("Content-Type", "application/json")
					->rawData($sContent)
					->execute();

				$oAnswer = json_decode($Core_Http->getDecompressedBody());

				if (!is_null($oAnswer))
				{
					if (property_exists($oAnswer, 'entries'))
					{
						$tag = '.tag';
						foreach ($oAnswer->entries as $oObject)
						{
							$oCurrentObject = new stdClass();
							$oCurrentObject->id = $oObject->id;
							$oCurrentObject->is_dir = $oObject->$tag == 'folder' ? 1 : 0;
							$oCurrentObject->bytes = 0;
							if (!$oCurrentObject->is_dir)
							{
								$oCurrentObject->bytes = $oObject->size;
								$oCurrentObject->datetime = Core_Date::timestamp2sql(Core_Date::date2timestamp($oObject->server_modified));
							}
							$oCurrentObject->path = trim($oObject->path_display, "/");
							$oCurrentObject->name = $oObject->name;
							self::$_cache[$sDirectory][] = $oCurrentObject;
						}
					}
					elseif (property_exists($oAnswer, 'error'))
					{
						throw new Core_Exception($oAnswer->error, array(), 0, FALSE);
					}
				}
			}

			return self::$_cache[$sDirectory];
		}
		else
		{
			throw new Core_Exception("Invalid token", array(), 0, FALSE);
		}
	}

	/**
	 * Download file from cloud
	 * @param string $sFileName file name
	 * @param string $sTargetPath target file path
	 * @param array $aParams options, e.g. for export add 'mimeType' => 'application/pdf'
	 * @return boolean
	 */
	public function download($sFileName, $sTargetDir, $aParams = array())
	{
		if (!is_null($this->_token))
		{
			$sFileName = Cloud_Controller::decode($sFileName);
			$oFile = $this->_getEntity('/' . $sFileName);

			Core_Session::start();

			$aFileData = Core_Array::get($_SESSION, 'HOSTCMS_CLOUD_DROPBOX_DOWNLOAD', array());

			if ($oFile)
			{
				if (!count($aFileData))
				{
					$aFileData['name'] = $oFile->name;
					$aFileData['size'] = $oFile->size;
					$aFileData['range_from'] = 0;
					$aFileData['size'] == 0 && $aFileData['size'] = 1;

					$aFileData['range_to'] = $this->chunkSize > $aFileData['size']
						? ''
						: $this->chunkSize - 1;

					$iFlag = 0;
				}
				else
				{
					$iFlag = FILE_APPEND;
				}

				$sBytesRange = $aFileData['range_from'] . '-' . $aFileData['range_to'];

				$sContent = json_encode(array(
					'path' => $oFile->id,
				));

				$Core_Http = Core_Http::instance('curl')
					->clear()
					->method('POST')
					->timeout($this->_timeout)
					->url("https://content.dropboxapi.com/2/files/download")
					->additionalHeader("Authorization", "Bearer {$this->_token}")
					->additionalHeader("Dropbox-API-Arg", $sContent)
					->additionalHeader("Range", "bytes={$sBytesRange}")
					->additionalHeader("Content-Type", "application/octet-stream")
					->execute();

				$sRawFileData = $Core_Http->getDecompressedBody();

				Core_File::mkdir($sTargetDir, CHMOD, TRUE);

				$sTargetPath = rtrim($sTargetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $aFileData['name'];

				if (file_put_contents($sTargetPath, $sRawFileData, $iFlag) === FALSE)
				{
					throw new Core_Exception('Can\'t write to file %name', array('%name' => $sTargetPath));
				}

				if ($aFileData['range_to'] == '')
				{
					// больше запросов не нужно
					$this->percent = -1;
					$aFileData = array();

					// Проверить размер файла
					if (Core_File::filesize($sTargetPath) != $oFile->size)
					{
						$this->percent = -2;
						unset($_SESSION['HOSTCMS_CLOUD_DROPBOX_DOWNLOAD']);
						throw new Core_Exception("Wrong file size");
					}
				}
				else
				{
					$this->percent = $aFileData['range_to'] * 100 / $aFileData['size'];
					$aFileData['range_from'] = $aFileData['range_to'] + 1;
					$aFileData['range_to'] += $this->chunkSize;
					if ($aFileData['range_from'] + $this->chunkSize > $aFileData['size'])
					{
						$aFileData['range_to'] = '';
					}
				}

				Core_Array::set($_SESSION, 'HOSTCMS_CLOUD_DROPBOX_DOWNLOAD', $aFileData);

				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Upload file into cloud
	 * @param string $sSourcePath file name
	 * @param string $sDestinationFileName
	 * @param array $aParams options, e.g. 'mimeType' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
	 * @return boolean
	 */
	public function upload($sSourcePath, $sDestinationFileName = NULL, $aParams = array())
	{
		if (!is_null($this->_token))
		{
			$this->dir = trim(str_replace('\\', '/', $this->dir), '/');

			$this->dir != '' && $this->dir = '/' . $this->dir . '/';

			$fileName = !is_null($sDestinationFileName)
				? $sDestinationFileName
				: basename($sSourcePath);

			$sTargetPath = $this->dir . $fileName;

			Core_Session::start();

			$aFileData = Core_Array::get($_SESSION, 'HOSTCMS_CLOUD_DROPBOX_UPLOAD', array());

			if (!count($aFileData))
			{
				$aFileData['size'] = filesize($sSourcePath);
				$aFileData['size'] == 0 && $aFileData['size'] = 1;
				$aFileData['offset'] = 0;

				// Получаем идентификатор сессии для загрузки
				$Core_Http = Core_Http::instance('curl')
					->clear()
					->method('POST')
					->timeout($this->_timeout)
					->url("https://content.dropboxapi.com/2/files/upload_session/start")
					->additionalHeader("Authorization", "Bearer {$this->_token}")
					->additionalHeader("Dropbox-API-Arg", json_encode(array('close' => FALSE)))
					->additionalHeader("Content-Type", "application/octet-stream")
					->execute();

				$aAnswer = json_decode($Core_Http->getDecompressedBody(), TRUE);

				if (isset($aAnswer['session_id']))
				{
					$aFileData['session_id'] = $aAnswer['session_id'];
				}
				else
				{
					return FALSE;
				}
			}

			$rFilePointer = fopen($sSourcePath, "rb");

			fseek($rFilePointer, $aFileData['offset'], SEEK_SET);

			$sRawFileData = fread($rFilePointer, $this->chunkSize);

			$bEOF = feof($rFilePointer);

			$aContent = array(
				'cursor' => array(
					'session_id' => $aFileData['session_id'],
					'offset' => $aFileData['offset']
				)
			);

			if ($bEOF)
			{
				$sURL = 'https://content.dropboxapi.com/2/files/upload_session/finish';

				$aContent['commit'] = array(
					'path' => $sTargetPath,
					'mode' => 'add',
					'autorename' => TRUE,
					'strict_conflict' => FALSE,
				);
			}
			else
			{
				$sURL = 'https://content.dropboxapi.com/2/files/upload_session/append_v2';
			}

			// Догружаем файл
			$Core_Http = Core_Http::instance('curl')
				->clear()
				->method('POST')
				->timeout($this->_timeout)
				->url($sURL)
				->additionalHeader('Authorization', "Bearer {$this->_token}")
				->additionalHeader("Dropbox-API-Arg", json_encode($aContent))
				->additionalHeader('Content-Type', 'application/octet-stream')
				->rawData($sRawFileData)
				->execute();

			if ($bEOF)
			{
				// Больше запросов не нужно
				$this->percent = 0;
				$aFileData = array();
				unset($_SESSION['HOSTCMS_CLOUD_DROPBOX_UPLOAD']);
			}
			else
			{
				$aFileData['offset'] = ftell($rFilePointer);
				$this->percent = $aFileData['offset'] * 100 / $aFileData['size'];
			}

			fclose($rFilePointer);

			Core_Array::set($_SESSION, 'HOSTCMS_CLOUD_DROPBOX_UPLOAD', $aFileData);

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Ger breadcrumbs
	 * @return array
	 */
	public function getBreadCrumbs()
	{
		$aBreadCrumbs = array();

		$aBreadCrumbs[] = array(
			'name' => Core::_('cloud.myDisk'),
			'id' => ''
		);

		$dirId = is_null($this->dirId)
			? $this->dir
			: $this->dirId;

		$oDir = $this->_getEntity($dirId);

		if ($oDir)
		{
			$aPath = explode('/', trim($oDir->path_display, '/'));

			for ($i = 0; $i < count($aPath); $i++)
			{
				$aBreadCrumbs[] = array(
					'name' => $aPath[$i],
					'id' => '/' . implode('/', array_slice($aPath, 0, $i + 1))
				);
			}
		}

		return $aBreadCrumbs;
	}

	/**
	 * Get entity object
	 * @param string $sEntityId entity
	 * @return object|FALSE
	 */
	protected function _getEntity($sEntityId)
	{
		if (!is_null($this->_token))
		{
			$sContent = json_encode(array(
				'path' => $sEntityId,
			));

			$Core_Http = Core_Http::instance('curl')
				->clear()
				->method('POST')
				->timeout($this->_timeout)
				->url("https://api.dropboxapi.com/2/files/get_metadata")
				->additionalHeader("Authorization", "Bearer {$this->_token}")
				->additionalHeader("Content-Type", "application/json")
				->rawData($sContent)
				->execute();

			$oAnswer = json_decode($Core_Http->getDecompressedBody());

			if (property_exists($oAnswer, 'error'))
			{
				$property = '.tag';
				throw new Core_Exception($oAnswer->error->$property, array(), 0, FALSE);
			}

			return $oAnswer;
		}

		return FALSE;
	}

	/**
	 * Delete file from cloud
	 * @param object $oObjectData file object
	 * @return boolean
	 */
	public function delete($oObjectData)
	{
		if (!is_null($this->_token))
		{
			$sContent = json_encode(array(
				'path' => $oObjectData->id,
			));

			Core_Http::instance('curl')
				->clear()
				->method('POST')
				->timeout($this->_timeout)
				->url("https://api.dropboxapi.com/2/files/delete_v2")
				->additionalHeader("Authorization", "Bearer {$this->_token}")
				->additionalHeader("Content-Type", "application/json")
				->rawData($sContent)
				->execute();

			return TRUE;
		}

		return FALSE;
	}
}
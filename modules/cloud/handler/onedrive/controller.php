<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * REST API https://docs.microsoft.com/ru-ru/onedrive/developer/rest-api/
 *
 * @package HostCMS
 * @subpackage Cloud
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Cloud_Handler_Onedrive_Controller extends Cloud_Controller
{
	/**
	 * Redirect uri
	 * @var mixed
	 */
	protected $_redirectUri = NULL;

	/**
	 * Constructor
	 * @param Cloud_Model $oCloud
	 */
	public function __construct(Cloud_Model $oCloud)
	{
		parent::__construct($oCloud);

		$aConfig = Core_Config::instance()->get('cloud_config', array());

		isset($aConfig['drivers'])
			&& $this->_config = Core_Array::get($aConfig['drivers'], 'onedrive');

		$this->chunkSize = Core_Array::get($this->_config, 'chunk');

		// if (strlen($this->_oCloud->access_token))
		// {
			// $AccessToken = json_decode($this->_oCloud->access_token);

			// $this->_token = is_object($AccessToken) && isset($AccessToken->access_token)
				// ? $AccessToken->access_token
				// : NULL;
		// }

		$oSite = Core_Entity::factory('Site', CURRENT_SITE);

		$oSiteAlias = $oSite->getCurrentAlias();

		$this->_redirectUri = !is_null($oSiteAlias)
			? 'https://' . $oSiteAlias->name . '/cloud-callback.php'
			: NULL;
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

		$aValues = array(
			'client_id' => $this->_oCloud->key,
			'response_type' => 'code',
			'redirect_uri' => $this->_redirectUri,
			'scope' => implode(' ', array(
				'files.read',
				'files.read.all',
				'files.readwrite',
				'files.readwrite.all',
				'offline_access'
			)),
			'response_mode' => 'query'
		);

		$query = http_build_query($aValues, '', '&', PHP_QUERY_RFC3986);

		return "https://login.microsoftonline.com/consumers/oauth2/v2.0/authorize?{$query}";
	}

	/**
	 * Get access token
	 * @return string
	 */
	public function getAccessToken($aValues = array())
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

		if (!count($aValues))
		{
			$aValues = array(
				'client_id' => $this->_oCloud->key,
				'redirect_uri' => $this->_redirectUri,
				'client_secret' => $this->_oCloud->secret,
				'code' => $this->_oCloud->code,
				'grant_type' => 'authorization_code',
			);
		}

		$Core_Http = Core_Http::instance('curl')
			->clear()
			->method('POST')
			->url("https://login.microsoftonline.com/consumers/oauth2/v2.0/token");

		foreach ($aValues as $key => $value)
		{
			$Core_Http->data($key, $value);
		}

		$Core_Http->execute();

		$sAnswer = $Core_Http->getDecompressedBody();

		if ($sAnswer === FALSE)
		{
			throw new Core_Exception("Server response: false", array(), 0, FALSE);
		}

		$aAnswer = json_decode($sAnswer, TRUE);

		if (isset($aAnswer['error']))
		{
			$this->_token = FALSE;
			throw new Core_Exception("Server response: {$aAnswer['error_description']}", array(), 0, FALSE);
		}

		$aAnswer['time'] = time();

		$sAnswer = json_encode($aAnswer);

		return $sAnswer;
	}

	protected $_token = NULL;

	/**
	 * Get token
	 * @return object
	 */
	public function getToken()
	{
		if (is_null($this->_token))
		{
			$this->_token = json_decode($this->_oCloud->access_token);

			if (is_object($this->_token))
			{
				// Проверяем токен на актуальность
				if (time() - $this->_token->time >= $this->_token->expires_in)
				{
					// Токен нужно обновить
					$sRefreshToken = $this->_token->refresh_token;
					$this->_token = json_decode(
						$this->getAccessToken(
							array(
								'client_id' => $this->_oCloud->key,
								'client_secret' => $this->_oCloud->secret,
								'refresh_token' => $sRefreshToken,
								'grant_type' => 'refresh_token'
							)
						)
					);

					$this->_token->refresh_token = $sRefreshToken;
					$this->_oCloud->access_token = json_encode($this->_token);
					$this->_oCloud->save();
				}
			}
		}

		return $this->_token;
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
		$oToken = $this->getToken();

		if (is_object($oToken))
		{
			$sDirectory = 'root';
			if (is_null($this->dirId))
			{
				$sDirectory = $this->dir = trim(str_replace('\\', '/', $this->dir));
			}
			else
			{
				$sDirectory = $this->dirId;
			}

			if (!isset(self::$_cache[$sDirectory]))
			{
				self::$_cache[$sDirectory] = array();

				$Core_Http = Core_Http::instance('curl')
					->clear()
					->method('GET')
					// ->url("https://graph.microsoft.com/v1.0/me/drive/items/5CB6802230C27F07!2357")
					->url("https://graph.microsoft.com/v1.0/me/drive/items/{$sDirectory}/children")
					->additionalHeader("Authorization", "Bearer {$oToken->access_token}")
					->execute();

				$oAnswer = json_decode($Core_Http->getDecompressedBody());

				if ($oAnswer)
				{
					if (property_exists($oAnswer, 'value'))
					{
						foreach ($oAnswer->value as $oObject)
						{
							$oCurrentObject = new stdClass();
							$oCurrentObject->id = $oObject->id;
							$oCurrentObject->bytes = 0;
							$oCurrentObject->is_dir = isset($oObject->folder) ? 1 : 0;
							if (!$oCurrentObject->is_dir)
							{
								$oCurrentObject->bytes = $oObject->size;
								$oCurrentObject->datetime = Core_Date::timestamp2sql(Core_Date::date2timestamp($oObject->lastModifiedDateTime));
							}
							$oCurrentObject->path = $oObject->id;
							$oCurrentObject->name = $oObject->name;

							self::$_cache[$sDirectory][] = $oCurrentObject;
						}
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
	 * @param string $fileId file id
	 * @param string $sTargetPath target file dir
	 * @return boolean
	 */
	public function download($fileId, $sTargetDir, $aParams = array())
	{
		$oToken = $this->getToken();

		if (!is_null($oToken))
		{
			$Core_Http = Core_Http::instance('curl')
				->clear()
				->method('GET')
				->timeout($this->_timeout)
				->url("https://graph.microsoft.com/v1.0/me/drive/items/{$fileId}/content" . (isset($aParams['format']) ? "?format={$aParams['format']}" : ''))
				->additionalHeader('Authorization', "Bearer {$oToken->access_token}")
				->execute();

			$aHeaders = $Core_Http->parseHeaders();
			$sStatus = Core_Array::get($aHeaders, 'status');
			$iStatusCode = $Core_Http->parseHttpStatusCode($sStatus);

			if ($iStatusCode == 200)
			{
				$sRawFileData = $Core_Http->getDecompressedBody();

				file_put_contents($sTargetDir, $sRawFileData);

				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Download file from cloud
	 * @param string $fileId file id
	 * @param string $sTargetPath target file dir
	 */
	public function downloadChunked($fileId, $sTargetDir)
	{
		$oToken = $this->getToken();

		if (!is_null($oToken))
		{
			Core_Session::start();

			$aFileData = Core_Array::get($_SESSION, 'HOSTCMS_CLOUD_ONEDRIVE_DOWNLOAD', array());

			$sFileName = Cloud_Controller::decode($fileId);
			$oFile = $this->_getEntity($sFileName);

			if (isset($oFile->name))
			{
				if (!count($aFileData))
				{
					$aFileData['name'] = $oFile->name;
					$aFileData['size'] = $oFile->size;
					$aFileData['size'] == 0 && $aFileData['size'] = 1;
					$aFileData['range_from'] = 0;

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

				try
				{
					$graph = '@microsoft.graph.downloadUrl';

					// https://docs.microsoft.com/en-us/onedrive/developer/rest-api/api/driveitem_get_content?view=odsp-graph-online#partial-range-downloads
					$Core_Http = Core_Http::instance('curl')
						->clear()
						->method('GET')
						->timeout($this->_timeout)
						->url($oFile->$graph)
						->additionalHeader('Authorization', "Bearer {$oToken->access_token}")
						->additionalHeader('Range', "bytes={$sBytesRange}")
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
						$this->percent = 0;
						$aFileData = array();

						// Проверить размер файла
						if (Core_File::filesize($sTargetPath) != $oFile->size)
						{
							$this->percent = 0;
							unset($_SESSION['HOSTCMS_CLOUD_ONEDRIVE_DOWNLOAD']);
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

					Core_Array::set($_SESSION, 'HOSTCMS_CLOUD_ONEDRIVE_DOWNLOAD', $aFileData);
				}
				catch (Exception $e)
				{
					echo "Error: {$e->getMessage()}";
				}
			}
		}
	}

	/**
	 * Prepare to uploading file into cloud
	 * @param string $sSourcePath file name
	 * @param string $sDestinationFileName
	 * @param array $aParams options, e.g. 'mimeType' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
	 * @return boolean|NULL
	 */
	public function upload($sSourcePath, $sDestinationFileName = NULL, $aParams = array())
	{
		$oToken = $this->getToken();

		if (!is_null($oToken))
		{
			if (Core_File::isFile($sSourcePath))
			{
				$sFileMime = isset($aParams['mimeType'])
					? $aParams['mimeType']
					: Core_Mime::getFileMime($sSourcePath);

				$iFileSize = Core_File::filesize($sSourcePath);

				$fileName = !is_null($sDestinationFileName)
					? $sDestinationFileName
					: basename($sSourcePath);

				$Core_Http = Core_Http::instance('curl')
					->clear()
					->method('PUT')
					->timeout($this->_timeout)
					->url('https://graph.microsoft.com/v1.0/me/drive/root:/' . rawurlencode($this->dir) . '/' . $fileName . ':/content')
					->additionalHeader('Authorization', "Bearer {$oToken->access_token}")
					->additionalHeader("Content-Length", $iFileSize)
					->additionalHeader("Content-Type", $sFileMime)
					->rawData(file_get_contents($sSourcePath))
					->execute();

				$aAnswer = json_decode($Core_Http->getDecompressedBody(), TRUE);

				return isset($aAnswer['id'])
					? $aAnswer['id']
					: NULL;
			}
		}

		return NULL;
	}

	/**
	 * Prepare to uploading file into cloud
	 * @param string $sSourcePath file name
	 * @param string $sDestinationFileName
	 * @param array $aParams options, e.g. 'mimeType' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
	 * @return boolean
	 */
	public function uploadChunked($sSourcePath, $sDestinationFileName = NULL, $aParams = array())
	{
		$oToken = $this->getToken();

		if (!is_null($oToken))
		{
			$iFileSize = Core_File::filesize($sSourcePath);

			$sDirectory = is_null($this->dirId)
				? $this->dir
				: $this->dirId;

			$fileName = !is_null($sDestinationFileName)
				? $sDestinationFileName
				: basename($sSourcePath);

			Core_Session::start();

			$aFileData = Core_Array::get($_SESSION, 'HOSTCMS_CLOUD_ONEDRIVE_UPLOAD', array());

			if (!count($aFileData))
			{
				$aFileData['size'] = filesize($sSourcePath);
				$aFileData['size'] == 0 && $aFileData['size'] = 1;
				$aFileData['offset'] = 0;

				$sContent = json_encode(array(
					'item' => array(
						'@microsoft.graph.conflictBehavior' => 'replace',
						'@odata.type' => 'microsoft.graph.driveItemUploadableProperties',
						'name' => $fileName
					)
				));

				// var_dump($sContent);

				$Core_Http = Core_Http::instance('curl')
					->clear()
					->method('POST')
					->timeout($this->_timeout)
					->url('https://graph.microsoft.com/v1.0/me/drive/' . rawurlencode($sDirectory) . ':/' . rawurlencode($fileName) . ':/createUploadSession')
					->additionalHeader("Content-Type", "application/json; charset=utf-8")
					->additionalHeader("Cache-Control", "no-cache")
					->additionalHeader("Pragma", "no-cache")
					->additionalHeader('Authorization', "Bearer {$oToken->access_token}")
					->rawData($sContent)
					->execute();

				$aAnswer = json_decode($Core_Http->getDecompressedBody(), TRUE);

				if (isset($aAnswer['uploadUrl']))
				{
					$aFileData['uploadUrl'] = $aAnswer['uploadUrl'];
				}
				else
				{
					return FALSE;
				}
			}

			$rFilePointer = fopen($sSourcePath, "rb");

			fseek($rFilePointer, $aFileData['offset'], SEEK_SET);

			$sRawFileData = fread($rFilePointer, $this->chunkSize);

			$iContentLength = strlen($sRawFileData);
			$bytes = $aFileData['offset'] + $iContentLength - 1;

			$Core_Http = Core_Http::instance('curl')
				->clear()
				->method('PUT')
				->timeout($this->_timeout)
				->url($aFileData['uploadUrl'])
				->additionalHeader('Authorization', "Bearer {$oToken->access_token}")
				->additionalHeader("Content-Length", $iContentLength)
				->additionalHeader("Content-Range", "bytes {$aFileData['offset']}-{$bytes}/{$iFileSize}")
				->rawData($sRawFileData)
				->execute();

			$bEOF = feof($rFilePointer);

			if ($bEOF)
			{
				// Больше запросов не нужно
				$this->percent = -1;
				$aFileData = array();
				unset($_SESSION['HOSTCMS_CLOUD_ONEDRIVE_UPLOAD']);
			}
			else
			{
				$aFileData['offset'] = ftell($rFilePointer);
				$this->percent = $aFileData['offset'] * 100 / $aFileData['size'];
			}

			fclose($rFilePointer);

			Core_Array::set($_SESSION, 'HOSTCMS_CLOUD_ONEDRIVE_UPLOAD', $aFileData);

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Get breadcrumbs
	 * @return array
	 */
	public function getBreadCrumbs()
	{
		$aBreadCrumbs = array();

		$dirId = is_null($this->dirId)
			? $this->dir
			: $this->dirId;

		do {
			$oDir = $this->_getEntity($dirId);

			if ($oDir && isset($oDir->id))
			{
				$aBreadCrumbs[] = array(
					'id' => $oDir->id,
					'name' => $oDir->name == 'root' ? Core::_('cloud.myDisk') : $oDir->name
				);

				isset($oDir->parentReference->id)
					&& $dirId = $oDir->parentReference->id;
			}
		} while ($oDir && isset($oDir->name) && $oDir->name != 'root');

		return array_reverse($aBreadCrumbs);
	}

	/**
	 * Get entity object
	 * @param string $sEntityId entity
	 * @return object|FALSE
	 */
	protected function _getEntity($sEntityId)
	{
		$oToken = $this->getToken();

		if (!is_null($oToken))
		{
			$Core_Http = Core_Http::instance('curl')
				->clear()
				->method('GET')
				->timeout($this->_timeout)
				->url("https://graph.microsoft.com/v1.0/me/drive/items/{$sEntityId}")
				->additionalHeader("Authorization", "Bearer {$oToken->access_token}")
				->execute();

			$oAnswer = json_decode($Core_Http->getDecompressedBody());

			return $oAnswer;
		}

		return FALSE;
	}

	/**
	 * Get edit URL
	 * @param string $fileId
	 * @param string $originalFilename
	 * @return string
	 */
	public function getEditUrl($fileId, $originalFilename)
	{
		$ext = Core_File::getExtension($originalFilename);

		$url = NULL;

		switch ($ext)
		{
			case 'docx':
			case 'xlsx':
			case 'pptx':
				$url = "https://onedrive.live.com/edit.aspx?resid={$fileId}&page=view";
			break;
		}

		return $url;
	}

	/**
	 * Edit
	 * @return boolean
	 */
	public function edit()
	{
		return TRUE;
	}

	/**
	 * Get button class
	 * @return string
	 */
	public function getButtonClass()
	{
		return 'btn btn-azure';
	}

	/**
	 * Delete file from cloud
	 * @param object $oObjectData file object
	 * @return boolean
	 */
	public function delete($oObjectData)
	{
		$oToken = $this->getToken();

		if (!is_null($oToken))
		{
			Core_Http::instance('curl')
				->clear()
				->method('DELETE')
				->timeout($this->_timeout)
				->url("https://graph.microsoft.com/v1.0/me/drive/items/{$oObjectData->id}")
				->additionalHeader("Authorization", "Bearer {$oToken->access_token}")
				->execute();

			return TRUE;
		}

		return FALSE;
	}
}
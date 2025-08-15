<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Google Drive REST API https://developers.google.com/drive/v3/reference/
 *
 * @package HostCMS
 * @subpackage Cloud
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Cloud_Handler_Google_Controller extends Cloud_Controller
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
			&& $this->_config = Core_Array::get($aConfig['drivers'], 'google');

		$this->chunkSize = Core_Array::get($this->_config, 'chunk');

		$oSite = Core_Entity::factory('Site', CURRENT_SITE);

		$oSiteAlias = $oSite->getCurrentAlias();

		$this->_redirectUri = !is_null($oSiteAlias)
			? (Core::httpsUses() ? 'https' : 'http') . '://' . $oSiteAlias->name . '/cloud-callback.php'
			: NULL;

		return $this;
	}

	/**
	 * Get breadcrumbs
	 * @return array
	 */
	public function getBreadCrumbs()
	{
		$dirId = is_null($this->dirId)
			? $this->_getDirIdByPath($this->dir)
			: $this->dirId;

		$aBreadCrumbs = array();

		do {
			$oGoogle_Service_Drive_File = $this->_getDirInfo($dirId);

			if ($oGoogle_Service_Drive_File)
			{
				$aBreadCrumbs[] = array(
					'id' => $oGoogle_Service_Drive_File->id,
					'name' => $oGoogle_Service_Drive_File->name
				);

				isset($oGoogle_Service_Drive_File->parents[0])
					&& $dirId = $oGoogle_Service_Drive_File->parents[0];
			}

		} while ($oGoogle_Service_Drive_File && isset($oGoogle_Service_Drive_File->parents[0]));

		return array_reverse($aBreadCrumbs);
	}

	/**
	 * Get id,name,parents
	 * @param string $sGroupId group id
	 * @return object|FALSE
	 */
	protected function _getDirInfo($sGroupId)
	{
		$AccessToken = $this->getToken();

		if ($AccessToken)
		{
			$Core_Http = Core_Http::instance('curl')
				->clear()
				->method('GET')
				->timeout($this->_timeout)
				->url("https://www.googleapis.com/drive/v3/files/{$sGroupId}?fields=id,name,parents")
				->additionalHeader("Authorization", "Bearer {$AccessToken->access_token}")
				->execute();

			$oGoogle_Service_Drive_File = json_decode($Core_Http->getDecompressedBody());

			return $oGoogle_Service_Drive_File;
		}

		return FALSE;
	}

	/**
	 * Get dir id by path
	 * @param string $sPath path to dir
	 * @return array
	 */
	protected function _getDirIdByPath($sPath)
	{
		$parentDir = 'root';

		$aExplode = explode('/', trim(str_replace('\\', '/', $sPath), ' /'));
		while ($subDir = array_pop($aExplode))
		{
			$parentDir = $this->_getDirId($parentDir, $subDir);
		}

		return $parentDir;
	}

	/**
	 *
	 * @param string $sParentId group parent id
	 * @param string $sDirectoryName directory name
	 * @return array
	 */
	protected function _getDirId($sParentId, $sDirectoryName)
	{
		$AccessToken = $this->getToken();

		if ($AccessToken)
		{
			try
			{
				$aParams = array(
					'q' => "name = '{$sDirectoryName}' and trashed = false and '{$sParentId}' in parents and mimeType = 'application/vnd.google-apps.folder'",
				);

				$Core_Http = Core_Http::instance('curl')
					->clear()
					->method('GET')
					->timeout($this->_timeout)
					->url("https://www.googleapis.com/drive/v3/files?" . http_build_query($aParams))
					->additionalHeader("Authorization", "Bearer {$AccessToken->access_token}")
					->execute();

				$oGoogle_Service_Drive_Folders = json_decode($Core_Http->getDecompressedBody())->files[0];

				if ($sParentId == 'root')
				{
					$Core_Http = Core_Http::instance('curl')
						->clear()
						->method('GET')
						->timeout($this->_timeout)
						->url("https://www.googleapis.com/drive/v3/files/root?fields=id,name,size,webContentLink,parents")
						->additionalHeader("Authorization", "Bearer {$AccessToken->access_token}")
						->execute();

					$oRootData = json_decode($Core_Http->getDecompressedBody());
				}

				return isset($oGoogle_Service_Drive_Folders->id)
					? $oGoogle_Service_Drive_Folders->id
					: FALSE;
			}
			catch (Exception $e)
			{
				// группа не найдена
				return FALSE;
			}
		}

		return FALSE;
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

		$sRedirectUri = urlencode($this->_redirectUri);

		// Full, permissive scope to access all of a user's files
		return "https://accounts.google.com/o/oauth2/auth?response_type=code&client_id={$this->_oCloud->key}&approval_prompt=force&access_type=offline&scope=https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fdrive&redirect_uri={$sRedirectUri}";
	}

	/**
	 * Get access token
	 * @param array $aParams params array
	 * @return string
	 */
	public function getAccessToken($aParams = array())
	{
		if (!count($aParams))
		{
			$aParams = array(
				'code' => $this->_oCloud->code,
				'redirect_uri' => $this->_redirectUri,
				'client_id' => $this->_oCloud->key,
				'scope' => '',
				'client_secret' => $this->_oCloud->secret,
				'grant_type' => 'authorization_code'
			);
		}

		if ($this->_oCloud->key == '')
		{
			$this->_token = FALSE;
			throw new Core_Exception("Invalid OAuth key");
		}

		if ($this->_oCloud->secret == '')
		{
			$this->_token = FALSE;
			throw new Core_Exception("Invalid OAuth secret");
		}

		if ($this->_oCloud->code == '')
		{
			$this->_token = FALSE;
			throw new Core_Exception("Invalid OAuth code");
		}

		$Core_Http = Core_Http::instance('curl')
			->clear()
			->method('POST')
			->timeout($this->_timeout)
			->url("https://accounts.google.com/o/oauth2/token");

		foreach ($aParams as $key => $value)
		{
			$Core_Http->data($key, $value);
		}

		$Core_Http->execute();

		$aAnswer = json_decode($Core_Http->getDecompressedBody(), TRUE);

		if (isset($aAnswer['error']))
		{
			$this->_token = FALSE;
			throw new Core_Exception("Server response: {$aAnswer['error']}");
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
		$AccessToken = $this->getToken();

		if ($AccessToken)
		{
			$sDirectory = 'root';
			if (is_null($this->dirId))
			{
				$sDirID = $this->_getDirIdByPath($this->dir);
				if ($sDirID)
				{
					$sDirectory = $sDirID;
				}
			}
			else
			{
				$sDirectory = $this->dirId;
			}

			if (!isset(self::$_cache[$sDirectory]))
			{
				self::$_cache[$sDirectory] = array();

				$aParams = array(
					'q' => "trashed = false and '{$sDirectory}' in parents and (mimeType = 'application/vnd.google-apps.folder' or (mimeType != 'application/vnd.google-apps.document' and mimeType != 'application/vnd.google-apps.map'))",
					'fields' => "files(id,mimeType,name,size,modifiedTime)"
				);

				$driveFilesUrl = "https://www.googleapis.com/drive/v3/files?" . http_build_query($aParams);

				$Core_Http = Core_Http::instance('curl')
					->clear()
					->method('GET')
					->timeout($this->_timeout)
					->url($driveFilesUrl)
					->additionalHeader("Authorization", "Bearer {$AccessToken->access_token}")
					->execute();

				$oGoogle_Service_Drive_FilesAndFolders = json_decode($Core_Http->getDecompressedBody());

				if ($oGoogle_Service_Drive_FilesAndFolders->files)
				{
					foreach ($oGoogle_Service_Drive_FilesAndFolders->files as $oGoogle_Service_Drive_FileOrFolder)
					{
						$oCurrentObject = new stdClass();
						$oCurrentObject->id = $oGoogle_Service_Drive_FileOrFolder->id;
						$oCurrentObject->is_dir = $oGoogle_Service_Drive_FileOrFolder->mimeType == 'application/vnd.google-apps.folder';
						$oCurrentObject->bytes = 0;
						if (!$oCurrentObject->is_dir)
						{
							$oCurrentObject->bytes = isset($oGoogle_Service_Drive_FileOrFolder->size) ? $oGoogle_Service_Drive_FileOrFolder->size : 0;
							$oCurrentObject->datetime = Core_Date::timestamp2sql(Core_Date::date2timestamp($oGoogle_Service_Drive_FileOrFolder->modifiedTime));
						}
						$oCurrentObject->path = $oGoogle_Service_Drive_FileOrFolder->id;
						$oCurrentObject->name = $oGoogle_Service_Drive_FileOrFolder->name;
						self::$_cache[$sDirectory][] = $oCurrentObject;
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
	 * @param array $aParams options, e.g. for export add 'mimeType' => 'application/pdf'
	 * @return boolean
	 */
	public function download($fileId, $sTargetDir, $aParams = array())
	{
		$AccessToken = $this->getToken();

		if ($AccessToken)
		{
			$aParams += array(
				'alt' => 'media'
			);

			$Core_Http = Core_Http::instance('curl')
				->clear()
				->method('GET')
				->timeout($this->_timeout)
				->url("https://www.googleapis.com/drive/v3/files/{$fileId}" . (isset($aParams['mimeType']) ? '/export' : '') . '?' . http_build_query($aParams))
				->additionalHeader('Authorization', "Bearer {$AccessToken->access_token}")
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
		$AccessToken = $this->getToken();

		if ($AccessToken)
		{
			$sFileName = Cloud_Controller::decode($fileId);

			Core_Session::start();

			$aFileData = Core_Array::get($_SESSION, 'HOSTCMS_CLOUD_GOOGLE_DOWNLOAD', array());
			if (!count($aFileData))
			{
				$Core_Http = Core_Http::instance('curl')
					->clear()
					->method('GET')
					->timeout($this->_timeout)
					->url("https://www.googleapis.com/drive/v3/files/{$sFileName}?fields=id,name,size,webContentLink")
					->additionalHeader("Authorization", "Bearer {$AccessToken->access_token}")
					->execute();

				$oGoogle_Service_Drive_File = json_decode($Core_Http->getDecompressedBody());

				$aFileData['name'] = $oGoogle_Service_Drive_File->name;
				$aFileData['size'] = $oGoogle_Service_Drive_File->size;
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
				// https://developers.google.com/drive/v3/web/manage-downloads
				$Core_Http = Core_Http::instance('curl')
					->clear()
					->method('GET')
					->timeout($this->_timeout)
					->url("https://www.googleapis.com/drive/v3/files/{$sFileName}?alt=media")
					->additionalHeader('Authorization', "Bearer {$AccessToken->access_token}")
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

				Core_Array::set($_SESSION, 'HOSTCMS_CLOUD_GOOGLE_DOWNLOAD', $aFileData);
			}
			catch (Exception $e)
			{
				echo "Error: {$e->getMessage()}";
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
		$AccessToken = $this->getToken();

		if ($AccessToken)
		{
			if (Core_File::isFile($sSourcePath))
			{
				$sFileMime = isset($aParams['mimeType'])
					? $aParams['mimeType']
					: Core_Mime::getFileMime($sSourcePath);

				// $iFileSize = Core_File::filesize($sSourcePath);

				$sSingleSeparator = "\r\n";
				$sDoubleSeparators = $sSingleSeparator . $sSingleSeparator;

				$bound = /*'---------==' . */strtoupper(uniqid(time()));

				$sRequest = "--{$bound}{$sSingleSeparator}";
				$sRequest .= "Content-Type: application/json; charset=UTF-8";
				$sRequest .= $sDoubleSeparators;

				$sDirectory = 'root';
				if (is_null($this->dirId))
				{
					($sDirID = $this->_getDirIdByPath($this->dir)) !== FALSE && $sDirectory = $sDirID;
				}
				else
				{
					$sDirectory = $this->dirId;
				}

				$aMetadata = array(
					'name' => !is_null($sDestinationFileName)
						? $sDestinationFileName
						: basename($sSourcePath),
					'parents' => array(
						$sDirectory
					),
					// Fix bug with 'Export only supports Google Docs'
					'mimeType' => 'application/vnd.google-apps.document'
				);

				$sRequest .= json_encode($aMetadata);
				$sRequest .= $sDoubleSeparators;

				$sRequest .= "--{$bound}{$sSingleSeparator}";
				$sRequest .= "Content-Type: {$sFileMime}";
				$sRequest .= $sDoubleSeparators;

				$sRequest .= Core_File::read($sSourcePath);
				$sRequest .= $sSingleSeparator;
				$sRequest .= "--{$bound}--{$sSingleSeparator}";

				// https://developers.google.com/drive/api/v3/manage-uploads#multipart
				$Core_Http = Core_Http::instance('curl')
					->clear()
					->method('POST')
					->timeout($this->_timeout)
					->url("https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart")
					->additionalHeader("Authorization", "Bearer {$AccessToken->access_token}")
					->additionalHeader("Content-Length", strlen($sRequest))
					->additionalHeader("Content-Type", "multipart/related; boundary=\"{$bound}\"")
					->rawData($sRequest)
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
	 * @param string $sSourcePath
	 * @param string $sDestinationFileName
	 * @param array $aParams options, e.g. 'mimeType' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
	 */
	public function uploadChunked($sSourcePath, $sDestinationFileName = NULL, $aParams = array())
	{
		$AccessToken = $this->getToken();

		if ($AccessToken)
		{
			$sFileMime = isset($aParams['mimeType'])
				? $aParams['mimeType']
				: Core_Mime::getFileMime($sSourcePath);

			$iFileSize = Core_File::filesize($sSourcePath);

			$sDirectory = 'root';
			if (is_null($this->dirId))
			{
				($sDirID = $this->_getDirIdByPath($this->dir)) !== FALSE && $sDirectory = $sDirID;
			}
			else
			{
				$sDirectory = $this->dirId;
			}

			$aContent = array(
				'name' => !is_null($sDestinationFileName)
					? $sDestinationFileName
					: basename($sSourcePath),
				'parents' => array(
					$sDirectory
				)
			);

			// DMS
			if (in_array(Core_File::getExtension($aContent['name']), array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx')))
			{
				// Fix bug with 'Export only supports Google Docs'
				$aContent['mimeType'] = 'application/vnd.google-apps.document';
			}

			$sContent = json_encode($aContent);

			Core_Session::start();

			if (is_null(Core_Array::get($_SESSION, 'HOSTCMS_CLOUD_GOOGLE_SESSION_URI')))
			{
				$Core_Http = Core_Http::instance('curl')
					->clear()
					->method('POST')
					->timeout($this->_timeout)
					->url("https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable")
					->additionalHeader("Authorization", "Bearer {$AccessToken->access_token}")
					->additionalHeader("Content-Length", strlen($sContent))
					->additionalHeader("Content-Type", "application/json; charset=UTF-8")
					->additionalHeader("X-Upload-Content-Type", $sFileMime)
					->additionalHeader("X-Upload-Content-Length", $iFileSize)
					->rawData($sContent)
					->execute();

				$aHeaders = $Core_Http->parseHeaders();

				$_SESSION['HOSTCMS_CLOUD_GOOGLE_SESSION_URI'] = $aHeaders['Location'];
			}

			$originalFilename = basename($sSourcePath);

			$aConformityFileNameToFileID = Core_Array::get($_SESSION, 'HOSTCMS_CLOUD_GOOGLE_CONFORMITY', array());
			$iUploadFilePosition = Core_Array::get($_SESSION, 'HOSTCMS_CLOUD_GOOGLE_UPLOAD_SEEK_POSITION_' . md5($originalFilename), 0);

			if (is_null(Core_Array::get($aConformityFileNameToFileID, $originalFilename)))
			{
				// query filter params
				$aParams = array(
					'q' => "trashed = false and '{$sDirectory}' in parents and mimeType != 'application/vnd.google-apps.folder' and mimeType != 'application/vnd.google-apps.document' and mimeType != 'application/vnd.google-apps.map'",
					'fields' => "files(id,originalFilename)"
				);

				$driveFilesUrl = "https://www.googleapis.com/drive/v3/files?" . http_build_query($aParams);

				$Core_Http = Core_Http::instance('curl')
					->clear()
					->method('GET')
					->timeout($this->_timeout)
					->url($driveFilesUrl)
					->additionalHeader("Authorization", "Bearer {$AccessToken->access_token}")
					->execute();

				$oGoogle_Service_Drive_Files = json_decode($Core_Http->getDecompressedBody());

				foreach ($oGoogle_Service_Drive_Files->files as $oGoogle_Service_Drive_File)
				{
					if ($originalFilename == $oGoogle_Service_Drive_File->originalFilename)
					{
						$aConformityFileNameToFileID[$originalFilename] = $oGoogle_Service_Drive_File->id;
						break;
					}
				}
			}

			$oGoogle_Http_MediaFileUpload = Core_Array::get($_SESSION, 'HOSTCMS_CLOUD_GOOGLE_HTTP_MEDIAFILEUPLOAD_OBJ');

			// Выгрузка по шагам
			$rUploadFile = fopen($sSourcePath, "rb");
			if ($rUploadFile)
			{
				fseek($rUploadFile, $iUploadFilePosition, SEEK_SET);
				$sUploadFileChunkData = fread($rUploadFile, $this->chunkSize);

				$iContentLength = strlen($sUploadFileChunkData);
				$bytes = $iUploadFilePosition + $iContentLength - 1;

				try
				{
					Core_Http::instance('curl')
						->clear()
						//->timeout(300)
						->timeout($this->_timeout)
						->method('PUT')
						->url($_SESSION['HOSTCMS_CLOUD_GOOGLE_SESSION_URI'])
						->additionalHeader("Authorization", "Bearer {$AccessToken->access_token}")
						->additionalHeader("Content-Length", $iContentLength)
						->additionalHeader("Content-Type", $sFileMime)
						->additionalHeader("Content-Range", "bytes {$iUploadFilePosition}-{$bytes}/{$iFileSize}")
						->rawData($sUploadFileChunkData)
						->execute();

					$bStatus = $sUploadFileChunkData;
				}
				catch (Exception $e)
				{
					Core_Array::set($_SESSION, 'HOSTCMS_CLOUD_GOOGLE_UPLOAD_SEEK_POSITION_' . md5($originalFilename), 0);
					$bStatus = new stdClass();
					$oGoogle_Http_MediaFileUpload = NULL;

					$_SESSION['HOSTCMS_CLOUD_GOOGLE_SESSION_URI'] = NULL;
				}

				if (!$bStatus || feof($rUploadFile))
				{
					// загрузка завершена, очищаем данные в сессии
					$aConformityFileNameToFileID = array();
					$iUploadFilePosition = 0;
					$this->percent = -1;
					$oGoogle_Http_MediaFileUpload = NULL;

					$_SESSION['HOSTCMS_CLOUD_GOOGLE_SESSION_URI'] = NULL;
				}
				else
				{
					// Get New position
					$iUploadFilePosition = ftell($rUploadFile);

					if (!$iFileSize)
					{
						$iFileSize = 1;
					}

					$this->percent = $iUploadFilePosition / $iFileSize * 100;
				}

				fclose($rUploadFile);

				Core_Array::set($_SESSION, 'HOSTCMS_CLOUD_GOOGLE_CONFORMITY', $aConformityFileNameToFileID);
				Core_Array::set($_SESSION, 'HOSTCMS_CLOUD_GOOGLE_UPLOAD_SEEK_POSITION_' . md5($originalFilename), $iUploadFilePosition);
				Core_Array::set($_SESSION, 'HOSTCMS_CLOUD_GOOGLE_HTTP_MEDIAFILEUPLOAD_OBJ', serialize($oGoogle_Http_MediaFileUpload));
			}
			else
			{
				throw new Core_Exception("Can`t be read source file ");
			}
		}
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
				$url = "https://docs.google.com/document/d/{$fileId}/edit";
			break;
			case 'xlsx':
				$url = "https://docs.google.com/spreadsheets/d/{$fileId}/edit";
			break;
			case 'pptx':
				$url = "https://docs.google.com/presentation/d/{$fileId}/edit";
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
		return 'btn btn-darkorange';
	}

	/**
	 * Delete file from cloud
	 * @param object $oObjectData file object
	 * @return boolean
	 */
	public function delete($oObjectData)
	{
		$AccessToken = $this->getToken();

		if ($AccessToken)
		{
			Core_Http::instance('curl')
				->clear()
				->method('DELETE')
				->timeout($this->_timeout)
				->url("https://www.googleapis.com/drive/v3/files/{$oObjectData->id}")
				->additionalHeader("Authorization", "Bearer {$AccessToken->access_token}")
				->execute();

			return TRUE;
		}

		return FALSE;
	}
}
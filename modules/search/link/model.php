<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Search_Link_Model
 *
 * @package HostCMS
 * @subpackage Search
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Search_Link_Model extends Core_Entity
{
	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'site' => array()
	);

	/**
	 * Constructor.
	 * @param int $id entity ID
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		if (is_null($id) && !$this->loaded())
		{
			$this->_preloadValues['site_id'] = defined('CURRENT_SITE') ? CURRENT_SITE : 0;
		}
	}

	/**
	 * File indexation
	 * @return array
	 * @hostcms-event search_link.onBeforeIndexingFile
	 * @hostcms-event search_link.onAfterIndexingFile
	 */
	protected function _indexFile($sPath)
	{
		$oSearch_Page = new stdClass();

		Core_Event::notify($this->_modelName . '.onBeforeIndexingFile', $this, array($oSearch_Page));

		$eventResult = Core_Event::getLastReturn();

		if (!is_null($eventResult))
		{
			return $eventResult;
		}

		// Read file content
		$content = Core_File::read($sPath);

		// Body
		preg_match('~<body(?:[^>]*)>(.*?)</body>~si', $content, $aBody);

		$body = Core_Array::get($aBody, 1, '');
		// Remove <header> and <footer> sections
		$body = preg_replace('~<header(?:[^>]*)>(.*?)</header>|<footer(?:[^>]*)>(.*?)</footer>~si', '', $body);

		$oSearch_Page->text = $body;

		// Title
		preg_match('~<title(?:[^>]*)>(.*)</title>~si', $content, $aTitle);

		$oSearch_Page->title = isset($aTitle[1])
			? html_entity_decode($aTitle[1])
			: 'Untitled';

		$oSiteAlias = $this->Site->getCurrentAlias();
		if ($oSiteAlias)
		{
			$oSearch_Page->url = ($this->Site->https ? 'https://' : 'http://')
				. $oSiteAlias->name
				. '/'
				. str_replace('\\', '/', substr($sPath, strlen(CMS_FOLDER)));
		}
		else
		{
			return NULL;
		}

		$oSearch_Page->size = mb_strlen($oSearch_Page->text);
		$oSearch_Page->site_id = $this->site_id;
		$oSearch_Page->datetime = date('Y-m-d H:i:s');
		$oSearch_Page->module = 10;
		$oSearch_Page->module_id = $this->site_id;
		$oSearch_Page->inner = 0;
		$oSearch_Page->module_value_type = 0; // search_page_module_value_type
		//$oSearch_Page->module_value_id = $this->id; // search_page_module_value_id
		$oSearch_Page->module_value_id = $this->_currentId++; // search_page_module_value_id

		$oSearch_Page->siteuser_groups = array(0);

		Core_Event::notify($this->_modelName . '.onAfterIndexingFile', $this, array($oSearch_Page));

		//$oSearch_Page->save();

		return $oSearch_Page;
	}

	protected $_indexedDirs = array();
	protected $_indexedFiles = array();
	protected $_currentId = 0;

	/**
	 * Dir indexation
	 * @return array
	 */
	protected function _indexDir($dirname, $expectedLimit)
	{
		$aReturn = array('pages' => array(), 'finished' => TRUE);
		$bFinished = TRUE;

		if (Core_File::isDir($dirname) && !Core_File::isLink($dirname))
		{
			$bAlreadyIndexed = in_array($dirname, $this->_indexedDirs);

			if ($bAlreadyIndexed)
			{
				return $aReturn;
			}

			$aExt = array_map('trim', explode(',', $this->ext));

			if ($dh = @opendir($dirname))
			{
				while (($file = readdir($dh)) !== FALSE)
				{
					if ($expectedLimit <= 0)
					{
						$bFinished = FALSE;
						break;
					}

					if ($file != '.' && $file != '..')
					{
						clearstatcache();
						$pathName = $dirname . DIRECTORY_SEPARATOR . $file;

						if (Core_File::isFile($pathName))
						{
							if (in_array(Core_File::getExtension($pathName), $aExt))
							{
								$bAlreadyIndexed = in_array($pathName, $this->_indexedFiles);

								if (!$bAlreadyIndexed)
								{
									$aReturn['pages'][] = $this->_indexFile($pathName);

									$this->_indexedFiles[] = $pathName;

									$expectedLimit--;
								}
							}
						}
						elseif (Core_File::isDir($pathName))
						{
							$aTmp = $this->_indexDir($pathName, $expectedLimit);

							if (count($aTmp['pages']))
							{
								$aReturn['pages'] = array_merge($aReturn['pages'], $aTmp['pages']);

								$expectedLimit -= count($aTmp['pages']);
							}
						}
					}
				}

				closedir($dh);
				clearstatcache();
			}

			if ($bFinished)
			{
				$this->_indexedDirs[] = $dirname;
			}
		}

		$aReturn['finished'] = $bFinished;

		return $aReturn;
	}

	/**
	 * Search indexation
	 * @return array
	 * @hostcms-event search_link.onBeforeIndexing
	 * @hostcms-event search_link.onAfterIndexing
	 */
	public function indexing($expectedLimit)
	{
		$aPages = array();
		$sPath = Core_File::pathCorrection(CMS_FOLDER . ltrim($this->url, '/\\'));
		$bFinihed = TRUE;

		$bSessionStarted = Core_Session::isStarted();
		
		!$bSessionStarted && Core_Session::start();
		
		$this->_indexedDirs = isset($_SESSION['search_dirs']) ? $_SESSION['search_dirs'] : array();
		$this->_indexedFiles = isset($_SESSION['search_files']) ? $_SESSION['search_files'] : array();
		$this->_currentId = isset($_SESSION['search_link_id']) ? $_SESSION['search_link_id'] : 1;
		
		!$bSessionStarted && Core_Session::close();

		if (file_exists($sPath))
		{
			if (Core_File::isDir($sPath))
			{
				$aTmp = $this->_indexDir(rtrim($sPath, '/\\'), $expectedLimit);
				$aPages = array_merge($aPages, $aTmp['pages']);
				$bFinihed = $aTmp['finished'];

				!$bSessionStarted && Core_Session::start();
				if ($bFinihed)
				{
					if (isset($_SESSION['search_dirs']))
					{
						unset($_SESSION['search_dirs']);
					}
					if (isset($_SESSION['search_files']))
					{
						unset($_SESSION['search_files']);
					}
					if (isset($_SESSION['search_link_id']))
					{
						unset($_SESSION['search_link_id']);
					}

					$this->_indexedDirs = $this->_indexedFiles = array();
				}
				else
				{
					$_SESSION['search_dirs'] = $this->_indexedDirs;
					$_SESSION['search_files'] = $this->_indexedFiles;
					$_SESSION['search_link_id'] = $this->_currentId;
				}
				!$bSessionStarted && Core_Session::close();
			}
			else
			{
				$aPages[] = $this->_indexFile($sPath);
				$bFinihed = TRUE;
			}
		}

		return array('pages' => $aPages, 'finished' => $bFinihed);
	}
}
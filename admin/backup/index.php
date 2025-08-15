<?php
/**
 * Backup.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
require_once('../../bootstrap.php');

Core_Auth::authorization($sModule = 'backup');

!defined('DISABLE_COMPRESSION') && define('DISABLE_COMPRESSION', TRUE);

// Код формы
$iAdmin_Form_Id = 41;
$sAdminFormAction = '/{admin}/backup/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$sPageTitle = Core::_('Backup.title');

$iCloud_Id = Core_Array::getGet('cloud_id');

if (Core::moduleIsActive('cloud') && $iCloud_Id)
{
	$oCloud = Core_Entity::factory('Cloud', $iCloud_Id);
	!is_null($oCloud) && $sPageTitle = $oCloud->name;
}

$actionDatasetId = Core::moduleIsActive('cloud') ? 1 : 0;

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Backup.title'))
	->pageTitle($sPageTitle);

clearstatcache();

// Create backup dir
if (!Core_File::isDir(BACKUP_DIR))
{
	Core_File::mkdir(BACKUP_DIR);
}

// Create .htaccess
if (!Core_File::isFile(BACKUP_DIR . '.htaccess'))
{
	Core_File::write(BACKUP_DIR . '.htaccess', '<IfModule !mod_authz_core.c>
	Order deny,allow
	Deny from all
</IfModule>
<IfModule mod_authz_core.c>
	Require all denied
</IfModule>');
}

// File download
if (Core_Array::getGet('download'))
{
	$oUser = Core_Auth::getCurrentUser();

	if (!$oUser->read_only && !$oUser->only_access_my_own)
	{
		// Получаем файлы директории
		if ($dh = opendir(BACKUP_DIR))
		{
			$fileName = Core_Array::getGet('download', '', 'str');
			// Читаем файлы и каталоги из данного каталога
			while (($file = readdir($dh)) !== FALSE)
			{
				$filePath = BACKUP_DIR . $file;
				if ($file != '.' && $file != '..' && filetype($filePath) != 'dir'
					&& sha1($file) == $fileName && file_exists($filePath)
				)
				{
					Core_File::download($filePath, $file, array('content_disposition' => 'attachment'));
					exit();
				}
			}
			closedir($dh);
		}
		throw new Core_Exception('Access denied');
	}
	else
	{
		throw new Core_Exception('Access forbidden');
	}
}

if (is_null($iCloud_Id))
{
	// Меню формы
	$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');

	// Элементы меню
	$oAdmin_Form_Entity_Menus->add(
		Admin_Form_Entity::factory('Menu')
			->name(Core::_('Backup.menu'))
			->add(
				Admin_Form_Entity::factory('Menu')
					->name(Core::_('Backup.db'))
					->icon('fa fa-database')
					->href(
						$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'databaseBackUp', NULL, $actionDatasetId, 0)
					)
					->onclick(
						$oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'databaseBackUp', NULL, $actionDatasetId, 0)
					)
			)
			->add(
				Admin_Form_Entity::factory('Menu')
					->name(Core::_('Backup.files'))
					->icon('fa fa-files-o')
					->href(
						$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'fileBackUp', NULL, $actionDatasetId, 0)
					)
					->onclick(
						$oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'fileBackUp', NULL, $actionDatasetId, 0)
					)
			)
	);

	// Добавляем все меню контроллеру
	$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Menus);
}

$oAdminFormAction_Backup_Controller_File = $oAdmin_Form->Admin_Form_Actions->getByName('fileBackUp');

if ($oAdminFormAction_Backup_Controller_File && $oAdmin_Form_Controller->getAction() == 'fileBackUp')
{
	$oBackup_Controller_File = Admin_Form_Action_Controller::factory(
		'Backup_Controller_File', $oAdminFormAction_Backup_Controller_File
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oBackup_Controller_File);
}

$oAdminFormAction_Backup_Controller_Download = $oAdmin_Form->Admin_Form_Actions->getByName('download');

if ($oAdminFormAction_Backup_Controller_Download && $oAdmin_Form_Controller->getAction() == 'download')
{
	$oBackup_Controller_Download = Admin_Form_Action_Controller::factory(
		'Backup_Controller_Download', $oAdminFormAction_Backup_Controller_Download
	);

	$oBackup_Controller_Download->cloud_id = $iCloud_Id;

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oBackup_Controller_Download);
}

$oAdminFormAction_Backup_Controller_Upload = $oAdmin_Form->Admin_Form_Actions->getByName('upload');

if ($oAdminFormAction_Backup_Controller_Upload && $oAdmin_Form_Controller->getAction() == 'upload')
{
	$oBackup_Controller_Upload = Admin_Form_Action_Controller::factory(
		'Backup_Controller_Upload', $oAdminFormAction_Backup_Controller_Upload
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oBackup_Controller_Upload);
}

$oAdminFormAction_Backup_Controller_Database = $oAdmin_Form->Admin_Form_Actions->getByName('databaseBackUp');

if ($oAdminFormAction_Backup_Controller_Database && $oAdmin_Form_Controller->getAction() == 'databaseBackUp')
{
	$oBackup_Controller_Database = Admin_Form_Action_Controller::factory(
		'Backup_Controller_Database', $oAdminFormAction_Backup_Controller_Database
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oBackup_Controller_Database);
}

if (Core::moduleIsActive('cloud'))
{
	if (strtolower(Core_Array::get($_SERVER, 'HTTP_X_REQUESTED_WITH', '', 'str')) == 'xmlhttprequest')
	{
		if (!is_null(Core_Array::getPost('download')))
		{
			$oCloud_Controller = Cloud_Controller::factory(Core_Array::getPost('cloud_id'));

			try
			{
				if ($oCloud_Controller)
				{
					if (method_exists($oCloud_Controller, 'downloadChunked'))
					{
						$oCloud_Controller->downloadChunked(Core_Array::getPost('file'), BACKUP_DIR);
						$aAnswer = $oCloud_Controller->percent;
					}
					else
					{
						$oCloud_Controller->download(Core_Array::getPost('file'), BACKUP_DIR);
						$aAnswer = 100;
					}
				}
				else
				{
					$aAnswer = array('error' => Core_Message::get('Controller not found', 'error'));
				}
			}
			catch (Exception $e)
			{
				$aAnswer = array('error' => Core_Message::get($e->getMessage(), 'error'));
			}

			Core::showJson($aAnswer);
		}

		if (!is_null(Core_Array::getPost('upload')))
		{
			$oCloud_Controller = Cloud_Controller::factory(Core_Array::getPost('cloud_id'));

			try
			{
				if ($oCloud_Controller)
				{
					Core_Database::instance()->query('SET SESSION wait_timeout = 28800');

					if (method_exists($oCloud_Controller, 'uploadChunked'))
					{
						$oCloud_Controller->uploadChunked(BACKUP_DIR . Core_Array::getPost('sourcename'));
						$aAnswer = $oCloud_Controller->percent;
					}
					else
					{
						$oCloud_Controller->upload(BACKUP_DIR . Core_Array::getPost('sourcename'));
						$aAnswer = $oCloud_Controller->percent;
					}
				}
				else
				{
					$aAnswer = array('error' => Core_Message::get('Controller not found', 'error'));
				}
			}
			catch (Exception $e)
			{
				$aAnswer = array('error' => Core_Message::get($e->getMessage(), 'error'));
			}

			Core::showJson($aAnswer);
		}
	}

	if (is_null($iCloud_Id))
	{
		// облачные хранилища
		$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(Core_Entity::factory('Cloud'));

		$oAdmin_Form_Dataset->addCondition(
				array('where' => array('active', '=', 1))
			)
			->addCondition(
				array('where' => array('site_id', '=', CURRENT_SITE))
			)
			->changeField('name', 'link', '/{admin}/backup/index.php?cloud_id={id}')
			->changeField('name', 'onclick', "$.adminLoad({path: '/{admin}/backup/index.php', additionalParams: 'cloud_id={id}', windowId: '{windowId}'}); return false");

		$oAdmin_Form_Controller->addDataset($oAdmin_Form_Dataset);
	}
	else
	{
		$sDir_Id = Core_Array::getGet('dir_id');

		$oBreadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');
		$oBreadcrumbs->add(
			Admin_Form_Entity::factory('Breadcrumb')
				->name(Core::_('Backup.title'))
				->href($oAdmin_Form_Controller->getAdminLoadHref(
					'/{admin}/backup/index.php', NULL, NULL, ''
				))
				->onclick($oAdmin_Form_Controller->getAdminLoadAjax(
					'/{admin}/backup/index.php', NULL, NULL, ''
				))
		);
		$oAdmin_Form_Controller->addEntity($oBreadcrumbs);

		try {
			// Добавляем пустые датасеты для достижения индекса действия 2
			$oAdmin_Form_Controller->addDataset(new Cloud_Empty_Dataset());
			$oAdmin_Form_Controller->addDataset(new Cloud_Empty_Dataset());

			$oCloud_Dir_Dataset = new Cloud_Dir_Dataset($iCloud_Id, $sDir_Id);

			$aDatasetBreadCrumbs = $oCloud_Dir_Dataset->getBreadCrumbs();
			foreach ($aDatasetBreadCrumbs as $aDatasetBreadCrumb)
			{
				$oBreadcrumbs->add(
				Admin_Form_Entity::factory('Breadcrumb')
					->name($aDatasetBreadCrumb['name'])
					->href($oAdmin_Form_Controller->getAdminLoadHref('/{admin}/backup/index.php', NULL, NULL, "cloud_id={$iCloud_Id}&dir_id={$aDatasetBreadCrumb['id']}"))
					->onclick($oAdmin_Form_Controller->getAdminLoadAjax('/{admin}/backup/index.php', NULL, NULL, "cloud_id={$iCloud_Id}&dir_id={$aDatasetBreadCrumb['id']}"))
				);
			}

			$oCloud_Dir_Dataset->changeField('name', 'link', "/{admin}/backup/index.php?cloud_id={$iCloud_Id}&dir_id={id}");
			$oCloud_Dir_Dataset->changeField('name', 'onclick', "$.adminLoad({path: '/{admin}/backup/index.php', additionalParams: 'cloud_id={$iCloud_Id}&dir_id={id}', windowId: '{windowId}'}); return false");
			$oAdmin_Form_Controller->addDataset($oCloud_Dir_Dataset);

			// File dataset
			$oCloud_File_Dataset = new Cloud_File_Dataset($iCloud_Id, $sDir_Id);
			$oCloud_File_Dataset->changeField('name', 'link', $oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'download', NULL, 3, '{hash}'));
			$oCloud_File_Dataset->changeField('name', 'onclick', $oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'download', NULL, 3, '{hash}'));

			$oCloud_File_Dataset
				->addExternalField('name')
				->addExternalField('datetime');

			$oAdmin_Form_Controller->addDataset($oCloud_File_Dataset);
		}
		catch (Exception $e)
		{
			$oAdmin_Form_Controller->addMessage(
				Core_Message::get($e->getMessage(), 'error')
			);
		}
	}
}

if (is_null($iCloud_Id))
{
	$oAdmin_Form_Dataset = new Wysiwyg_Filemanager_Dataset('file');
	$oAdmin_Form_Dataset
		->modelName('Backup_File')
		->setPath(BACKUP_DIR);

	// Добавляем источник данных контроллеру формы
	$oAdmin_Form_Controller->addDataset(
		$oAdmin_Form_Dataset
	);
}

// Показ формы
$oAdmin_Form_Controller->execute();
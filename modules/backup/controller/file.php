<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Backup. Контроллер создания резервной копии файлов
 *
 * @package HostCMS
 * @subpackage Backup
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Backup_Controller_File extends Admin_Form_Action_Controller
{
	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return self
	 */
	public function execute($operation = NULL)
	{
		Core_Database::instance()->query('SET SESSION wait_timeout = 28800');

		$oBackup_Controller = new Backup_Controller();
		return $oBackup_Controller->backupFiles(BACKUP_DIR);
	}
}
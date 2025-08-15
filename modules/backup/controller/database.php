<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Backup. Контроллер создания резервной копии базы данных
 *
 * @package HostCMS
 * @subpackage Backup
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Backup_Controller_Database extends Admin_Form_Action_Controller
{
	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return self
	 */
	public function execute($operation = NULL)
	{
		if (!defined('DENY_INI_SET') || !DENY_INI_SET)
		{
			@set_time_limit(21600);
			ini_set('max_execution_time', '21600');
		}

		$oBackup_Controller = new Backup_Controller();
		$oBackup_Controller->backupDatabase(BACKUP_DIR);

		return $this;
	}
}
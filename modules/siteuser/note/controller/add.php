<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Note_Controller_Add
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Siteuser_Note_Controller_Add extends Crm_Note_Controller_Add
{
	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return self
	 */
	public function execute($operation = NULL)
	{
		parent::execute();

		$iSiteuserId = Core_Array::getGet('siteuser_id', 0, 'int');

		$oCrm_Note = $this->_object;

		$oSiteuser = Core_Entity::factory('Siteuser', $iSiteuserId);
		$oSiteuser->add($oCrm_Note);

		$aFiles = Core_Array::getFiles('file', array());

		if (is_array($aFiles) && isset($aFiles['name']))
		{
			$oCrm_Note->dir = $oSiteuser->getDirHref();
			$oCrm_Note->save();

			$iCount = count($aFiles['name']);

			for ($i = 0; $i < $iCount; $i++)
			{
				$aFile = array(
					'name' => $aFiles['name'][$i],
					'tmp_name' => $aFiles['tmp_name'][$i],
					'size' => $aFiles['size'][$i]
				);

				if (intval($aFile['size']) > 0)
				{
					$oCrm_Note_Attachment = Core_Entity::factory('Crm_Note_Attachment');
					$oCrm_Note_Attachment->crm_note_id = $oCrm_Note->id;

					$oCrm_Note_Attachment
						->setDir(CMS_FOLDER . $oCrm_Note->dir)
						->setHref($oSiteuser->getDirHref())
						->saveFile($aFile['tmp_name'], $aFile['name']);
				}
			}
		}

		$oModule = Core_Entity::factory('Module')->getByPath('siteuser');

		// Добавляем уведомление
		$oNotification = Core_Entity::factory('Notification')
			->title(Core::_('Siteuser_Note.add_notification', $oSiteuser->login, FALSE))
			->description(
				html_entity_decode(strip_tags($this->_object->text), ENT_COMPAT, 'UTF-8')
			)
			->datetime(Core_Date::timestamp2sql(time()))
			->module_id($oModule->id)
			->type(6) // 6 - Добавлена заметка
			->entity_id($oSiteuser->id)
			->save();

		// Связываем уведомление с сотрудниками
		Core_Entity::factory('User', $oSiteuser->user_id)->add($oNotification);

		$windowId = $this->_Admin_Form_Controller->getWindowId();

		// var_dump($windowId);

		$aExplodeWindowId = explode('-', $windowId);

		if (strpos($windowId, '-siteuser-notes') !== FALSE)
		{
			$this->addMessage("<script>$(function() {
				$.adminLoad({ path: hostcmsBackend + '/siteuser/timeline/index.php', additionalParams: 'siteuser_id={$oSiteuser->id}', windowId: '{$aExplodeWindowId[0]}-siteuser-timeline' });
			});</script>");
		}
		elseif (strpos($windowId, '-siteuser-timeline') !== FALSE)
		{
			$this->addMessage("<script>$(function() {
				$.adminLoad({ path: hostcmsBackend + '/siteuser/note/index.php', additionalParams: 'siteuser_id={$oSiteuser->id}', windowId: '{$aExplodeWindowId[0]}-siteuser-notes' });
			});</script>");
		}
	}
}
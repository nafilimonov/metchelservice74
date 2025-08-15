<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Media_Controller_Upload
 *
 * @package HostCMS
 * @subpackage Media
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Media_Controller_Upload extends Admin_Form_Action_Controller
{
	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return self
	 */
	public function execute($operation = NULL)
	{
		$media_group_id = Core_Array::getGet('media_group_id', 0, 'int');

		// $entity_id = Core_Array::getGet('entity_id', 0, 'int');
		// $entity_type = Core_Array::getGet('entity_type', '', 'trim');

		$aFiles = Core_Array::getFiles('file', array());

		if (is_array($aFiles) && isset($aFiles['name']))
		{
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
					$oMedia_Item = Core_Entity::factory('Media_Item');
					$oMedia_Item->name = $aFile['name'];
					$oMedia_Item->media_group_id = $media_group_id;
					$oMedia_Item->save();

					$bForceUpdate = FALSE;

					if (Core_File::isValidExtension($aFile['name'], Core::$mainConfig['availableExtension']))
					{
						$oMedia_Item->saveOriginalFile($aFile['name'], $aFile['tmp_name']);
						$bForceUpdate = TRUE;
					}
					else
					{
						Core_Message::get(
							Core::_('Core.extension_does_not_allow', Core_File::getExtension($aFile['name'])),
							'error'
						);
					}

					$type = Media_Controller::getType($oMedia_Item);

					$oMedia_Item->type = $type;
					$oMedia_Item->save();

					if ($oMedia_Item->file != '')
					{
						if ($type == 1)
						{
							$aMedia_Formats = Core_Entity::factory('Media_Format')->getAllBySite_id(CURRENT_SITE);
							foreach ($aMedia_Formats as $oMedia_Format)
							{
								$oMedia_Format->saveImage($oMedia_Item, $bForceUpdate);
							}
						}
					}
				}
			}
		}
	}
}
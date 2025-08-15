<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Media.
 *
 * @package HostCMS
 * @subpackage Media
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Media_Controller_Add extends Admin_Form_Action_Controller
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		'get',
	);

	/**
	 * Array of availabale types
	 * @var array
	 */
	protected $_aAvailableType = array(
		'informationsystem_group',
		'informationsystem_item',
		'shop_group',
		'shop_item',
		'structure'
	);

	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 */
	public function execute($operation = NULL)
	{
		if (isset($this->get['entity_id']))
		{
			$type = Core_Array::get($this->get, 'entity_type', '', 'trim');
			$entity_id = Core_Array::get($this->get, 'entity_id', 0, 'int');

			if ($entity_id && in_array($type, $this->_aAvailableType))
			{
				// Идентификаторы переносимых указываем скрытыми полями в форме, чтобы не превысить лимит GET
				$aChecked = $this->_Admin_Form_Controller->getChecked();

				// Clear checked list
				$this->_Admin_Form_Controller->clearChecked();

				foreach ($aChecked as $datasetKey => $checkedItems)
				{
					foreach ($checkedItems as $media_item_id => $value)
					{
						$oEntities = Core_Entity::factory('Media_' . $type);
						$oEntities->queryBuilder()
							->where('media_' . $type . 's.media_item_id', '=', $media_item_id)
							->where('media_' . $type . 's.' . $type . '_id', '=', $entity_id);

						if (!$oEntities->getCount(FALSE))
						{
							$prop = $type . '_id';

							$oEntity = Core_Entity::factory('Media_' . $type);
							$oEntity->media_item_id = $media_item_id;
							$oEntity->$prop = $entity_id;
							$oEntity->save();
						}
					}
				}

				$this->addMessage("<script>$(function() {
					$('#" . $this->get['modalWindowId'] . "').parents('.modal').modal('hide');

					mainFormLocker.unlock();
					$.adminLoad({ path: hostcmsBackend + '/media/index.php', additionalParams: 'entity_id=" . $this->get['entity_id'] . "&type=" . $this->get['entity_type'] . "&parentWindowId=" . $this->get['parentWindowId'] . "&_module=0', windowId: '" . $this->get['parentWindowId'] . "', loadingScreen: false });
				});</script>");
			}
		}

		return TRUE;
	}
}
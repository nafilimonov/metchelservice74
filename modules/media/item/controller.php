<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Media_Controller
 *
 * @package HostCMS
 * @subpackage Media
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2024 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Media_Item_Controller extends Core_Servant_Properties
{
	static public function getValues($oObject)
	{
		$modelName = $oObject->getModelName();

		$oEntities = Core_Entity::factory('Media_' . $modelName);
		$oEntities->queryBuilder()
			->select('media_' . $modelName . 's.*')
			->join('media_items', 'media_' . $modelName . 's.media_item_id', '=', 'media_items.id')
			->where('media_' . $modelName . 's.' . $modelName . '_id', '=', $oObject->id)
			->where('media_items.deleted', '=', 0)
			->clearOrderBy()
			->orderBy('sorting');

		return $oEntities->findAll();
	}
}
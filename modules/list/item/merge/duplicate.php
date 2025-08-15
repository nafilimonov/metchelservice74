<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * List_Item_Merge_Duplicate
 *
 * @package HostCMS
 * @subpackage List
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2021 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class List_Item_Merge_Duplicate extends Admin_Form_Action_Controller
{
	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return self
	 */
	public function execute($list_id = NULL)
	{
		if ($list_id)
		{
			$oList = Core_Entity::factory('List', $list_id);

			$oList_Items = $oList->List_Items;
			$oList_Items->queryBuilder()
				->groupBy('value')
				->groupBy('parent_id')
				->having('COUNT(value)', '>', 1);

			$aList_Items = $oList_Items->findAll(FALSE);

			foreach ($aList_Items as $oList_Item)
			{
				$oDuplicate_List_Items = $oList->List_Items;
				$oDuplicate_List_Items->queryBuilder()
					->where('id', '!=', $oList_Item->id)
					->where('value', '=', $oList_Item->value)
					->where('parent_id', '=', $oList_Item->parent_id);

				$aDuplicate_List_Items = $oDuplicate_List_Items->findAll(FALSE);

				foreach ($aDuplicate_List_Items as $oDuplicate_List_Item)
				{
					$oList_Item->merge($oDuplicate_List_Item);
				}
			}
		}

		return $this;
	}
}
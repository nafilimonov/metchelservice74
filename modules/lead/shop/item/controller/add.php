<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Lead.
 *
 * @package HostCMS
 * @subpackage Lead
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Lead_Shop_Item_Controller_Add extends Admin_Form_Action_Controller
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		'shop_item_name',
		'lead_id',
		'shop_item_id',
		'shop_item_rate',
	);

	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 */
	public function execute($operation = NULL)
	{
		if (strlen($this->shop_item_name))
		{
			if ($this->lead_id && ($oLead = Core_Entity::factory('Lead')->find($this->lead_id)) && !is_null($oLead->id))
			{
				$oShop_Item = Core_Entity::factory('Shop_Item')->find($this->shop_item_id);

				$oUser = Core_Auth::getCurrentUser();

				$oLead_Shop_Item = Core_Entity::factory('Lead_Shop_Item');

				$oLead_Shop_Item->lead_id = $this->lead_id;
				$oLead_Shop_Item->shop_item_id = !is_null($oShop_Item->id) ? $oShop_Item->id : 0;
				$oLead_Shop_Item->shop_currency_id = !is_null($oShop_Item->id) ? $oShop_Item->shop_currency_id : $oLead->shop_currency_id;
				$oLead_Shop_Item->name = !is_null($oShop_Item->id) ? $oShop_Item->name : $this->shop_item_name;
				$oLead_Shop_Item->quantity = 1;
				$oLead_Shop_Item->price = !is_null($oShop_Item->id) ? $oShop_Item->price : 0;
				$oLead_Shop_Item->marking = !is_null($oShop_Item->id) ? $oShop_Item->marking : '';
				$oLead_Shop_Item->rate = !is_null($oShop_Item->id) ? $this->shop_item_rate : 0;
				$oLead_Shop_Item->user_id = $oUser->id;
				$oLead_Shop_Item->type = !is_null($oShop_Item->id) ? $oShop_Item->type : 0;

				$oLead_Shop_Item->save();

				$this->addMessage("<script>$(function() {
					var jA = $('li[data-type=timeline] a');
					if (jA.length)
					{
						$.adminLoad({ path: jA.data('path'), additionalParams: jA.data('additional'), windowId: jA.data('window-id') });
					}
				});</script>");
			}
		}

		return NULL;
	}
}
<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Shop_Cart_Model
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_Shop_Cart_Model extends Shop_Model
{
	/**
	 * Name of the table
	 * @var string
	 */
	protected $_tableName = 'shops';

	/**
	 * Model name
	 * @var mixed
	 */
	protected $_modelName = 'siteuser_shop_cart';

	/**
	 * Backend badge
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function nameBadge($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		$siteuser_id = Core_Array::getGet('siteuser_id', 0);

		if ($siteuser_id)
		{
			$countShopCarts = 0;

			$oShop_Carts = Core_Entity::factory('Shop_Cart');
			$oShop_Carts->queryBuilder()
				->join('shop_items', 'shop_carts.shop_item_id', '=', 'shop_items.id')
				->where('shop_carts.siteuser_id', '=', $siteuser_id)
				->where('shop_carts.shop_id', '=', $this->id)
				->where('shop_items.deleted', '=', 0);

			$aShop_Carts = $oShop_Carts->findAll(FALSE);

			foreach ($aShop_Carts as $oShop_Cart)
			{
				$countShopCarts += $oShop_Cart->quantity;
			}

			$countShopCarts && Core_Html_Entity::factory('Span')
				->class('badge badge-hostcms badge-square')
				->value('<i class="fa fa-shopping-basket"></i> ' . $countShopCarts)
				->title(Core::_('Siteuser.in_cart', $countShopCarts))
				->execute();
		}
	}
}
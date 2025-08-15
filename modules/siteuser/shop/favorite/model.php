<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Shop_Favorite_Model
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_Shop_Favorite_Model extends Shop_Model
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
	protected $_modelName = 'siteuser_shop_favorite';

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
			$oShop_Favorites = Core_Entity::factory('Shop_Favorite');
			$oShop_Favorites->queryBuilder()
				->where('shop_favorites.siteuser_id', '=', $siteuser_id)
				->where('shop_favorites.shop_id', '=', $this->id);

			$countShopFavorites = $oShop_Favorites->getCount(FALSE);

			$countShopFavorites && Core_Html_Entity::factory('Span')
				->class('badge badge-hostcms badge-square')
				->value('<i class="fa-regular fa-heart"></i> ' . $countShopFavorites)
				->title(Core::_('Siteuser.in_favorite', $countShopFavorites))
				->execute();
		}
	}
}